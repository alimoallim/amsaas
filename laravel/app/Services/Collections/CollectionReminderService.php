<?php

namespace App\Services\Collections;

use App\Enums\CollectionReminderType;
use App\Jobs\SendCollectionReminderJob;
use App\Models\Agreement;
use App\Models\CollectionReminderLog;
use App\Models\Company;
use App\Models\DelinquencyFlag;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CollectionReminderService
{
    /**
     * @return array{queued: int, skipped: int}
     */
    public function queueDueReminders(Company $company, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $stats = ['queued' => 0, 'skipped' => 0];

        $invoices = MonthlyInvoice::query()
            ->where('company_id', $company->id)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid', 'overdue'])
            ->whereRaw('balance_due > 0')
            ->whereNotNull('due_date')
            ->get();

        foreach ($invoices as $invoice) {
            foreach (CollectionReminderType::automated() as $type) {
                if (! $this->matchesSchedule($invoice, $type, $asOf)) {
                    continue;
                }

                $result = $this->queueReminder($invoice, $type);
                $stats[$result]++;
            }
        }

        return $stats;
    }

    /**
     * @param  list<string>  $flagIds
     * @return array{queued: int, skipped: int, failed: int}
     */
    public function queueManualReminders(User $actor, array $flagIds): array
    {
        $stats = ['queued' => 0, 'skipped' => 0, 'failed' => 0];

        $flags = DelinquencyFlag::query()
            ->where('company_id', $actor->company_id)
            ->whereNull('resolved_at')
            ->whereIn('id', $flagIds)
            ->with('monthlyInvoice')
            ->get();

        foreach ($flags as $flag) {
            $invoice = $flag->monthlyInvoice;
            if (! $invoice) {
                $stats['failed']++;

                continue;
            }

            $result = $this->queueReminder($invoice, CollectionReminderType::Manual, $actor, $flag);
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    public function logsForTenant(User $user, string $tenantId, int $limit = 50): array
    {
        $rows = CollectionReminderLog::query()
            ->where('company_id', $user->company_id)
            ->where('tenant_id', $tenantId)
            ->with(['monthlyInvoice:id,invoice_number'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (CollectionReminderLog $log) => [
                'id' => $log->id,
                'reminder_type' => $log->reminder_type?->value,
                'reminder_label' => $log->reminder_type?->label(),
                'channel' => $log->channel,
                'status' => $log->status,
                'invoice_number' => $log->monthlyInvoice?->invoice_number,
                'recipient' => $log->recipient,
                'sent_at' => $log->sent_at?->toIso8601String(),
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'tenant_id' => $tenantId,
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    public function dispatchLog(CollectionReminderLog $log): void
    {
        $invoice = MonthlyInvoice::query()
            ->with(['apartment.building'])
            ->find($log->monthly_invoice_id);

        if (! $invoice) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Invoice not found.',
            ]);

            return;
        }

        $tenant = $log->tenant_id
            ? Tenant::query()->find($log->tenant_id)
            : $this->resolveTenant($invoice);

        if ($tenant?->reminder_opt_out) {
            $log->update([
                'status' => 'skipped_opt_out',
                'error_message' => 'Tenant opted out of reminders.',
            ]);

            return;
        }

        $recipient = trim((string) ($tenant?->email ?? ''));
        if ($recipient === '') {
            $log->update([
                'status' => 'skipped_no_email',
                'error_message' => 'Tenant has no email address.',
            ]);

            return;
        }

        $tenantName = $this->tenantDisplayName($tenant);

        try {
            \Illuminate\Support\Facades\Mail::to($recipient)->send(
                new \App\Mail\CollectionReminderMail(
                    $invoice,
                    $tenantName,
                    $log->reminder_type ?? CollectionReminderType::Manual,
                )
            );

            $log->update([
                'status' => 'sent',
                'recipient' => $recipient,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function matchesSchedule(
        MonthlyInvoice $invoice,
        CollectionReminderType $type,
        Carbon $asOf,
    ): bool {
        $offset = $type->dayOffset();
        if ($offset === null || ! $invoice->due_date) {
            return false;
        }

        $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
        $triggerDate = $dueDate->copy()->addDays($offset);

        return $triggerDate->equalTo($asOf);
    }

    /**
     * @return 'queued'|'skipped'
     */
    protected function queueReminder(
        MonthlyInvoice $invoice,
        CollectionReminderType $type,
        ?User $actor = null,
        ?DelinquencyFlag $flag = null,
    ): string {
        $existing = CollectionReminderLog::query()
            ->where('monthly_invoice_id', $invoice->id)
            ->where('reminder_type', $type->value)
            ->first();

        if ($existing && in_array($existing->status, ['queued', 'sent'], true)) {
            return 'skipped';
        }

        $tenant = $this->resolveTenant($invoice);

        if ($tenant?->reminder_opt_out) {
            return 'skipped';
        }

        if ($existing) {
            $existing->update([
                'status' => 'queued',
                'error_message' => null,
                'triggered_by' => $actor?->id,
                'delinquency_flag_id' => $flag?->id,
            ]);
            $log = $existing->fresh();
        } else {
            $log = CollectionReminderLog::query()->create([
                'id' => (string) Str::uuid(),
                'company_id' => $invoice->company_id,
                'tenant_id' => $tenant?->id,
                'monthly_invoice_id' => $invoice->id,
                'delinquency_flag_id' => $flag?->id,
                'reminder_type' => $type,
                'channel' => 'email',
                'status' => 'queued',
                'triggered_by' => $actor?->id,
            ]);
        }

        SendCollectionReminderJob::dispatch($log->id);

        return 'queued';
    }

    protected function resolveTenant(MonthlyInvoice $invoice): ?Tenant
    {
        if ($invoice->contract_type !== 'rental') {
            return null;
        }

        $agreement = Agreement::query()
            ->with('tenant')
            ->find($invoice->contract_id);

        return $agreement?->tenant;
    }

    protected function tenantDisplayName(?Tenant $tenant): string
    {
        if (! $tenant) {
            return 'Tenant';
        }

        $display = trim((string) ($tenant->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        $composed = trim(collect([$tenant->first_name, $tenant->last_name])->filter()->implode(' '));

        return $composed !== '' ? $composed : 'Tenant';
    }
}
