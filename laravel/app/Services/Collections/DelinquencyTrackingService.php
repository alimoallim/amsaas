<?php

namespace App\Services\Collections;

use App\Enums\DelinquencyEscalationStage;
use App\Models\Agreement;
use App\Models\Company;
use App\Models\DelinquencyFlag;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DelinquencyTrackingService
{
    /** Days since first overdue before 2nd notice. */
    public const DAYS_TO_SECOND_NOTICE = 15;

    /** Days since first overdue before legal handoff. */
    public const DAYS_TO_LEGAL_HANDOFF = 45;

    /**
     * @return array<string, int>
     */
    public function processCompany(Company $company, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $stats = [
            'flagged' => 0,
            'created' => 0,
            'escalated' => 0,
            'status_updated' => 0,
        ];

        $invoices = MonthlyInvoice::query()
            ->where('company_id', $company->id)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid', 'overdue'])
            ->whereRaw('balance_due > 0')
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->status !== 'overdue') {
                $invoice->update(['status' => 'overdue']);
                $stats['status_updated']++;
            }

            $firstOverdueDate = $this->firstOverdueDateFor($invoice);

            $flag = DelinquencyFlag::query()->firstOrNew([
                'monthly_invoice_id' => $invoice->id,
            ]);

            if ($flag->exists && $flag->resolved_at !== null) {
                continue;
            }

            if (! $flag->exists) {
                $flag->fill([
                    'company_id' => $company->id,
                    'first_overdue_date' => $firstOverdueDate,
                    'escalation_stage' => DelinquencyEscalationStage::FirstNotice,
                    'stage_updated_at' => now(),
                ]);
                $flag->save();
                $stats['created']++;
            }

            $newStage = $this->escalationStageFor($flag->first_overdue_date, $asOf);
            if ($flag->escalation_stage !== $newStage) {
                $flag->update([
                    'escalation_stage' => $newStage,
                    'stage_updated_at' => now(),
                ]);
                $stats['escalated']++;
            }

            $stats['flagged']++;
        }

        return $stats;
    }

    public function resolveForInvoice(MonthlyInvoice $invoice): void
    {
        DelinquencyFlag::query()
            ->where('monthly_invoice_id', $invoice->id)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function listForCompany(
        User $user,
        ?string $escalationStage = null,
        ?string $buildingId = null,
        ?Carbon $asOf = null,
    ): array {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $query = DelinquencyFlag::query()
            ->where('company_id', $user->company_id)
            ->whereNull('resolved_at')
            ->with(['monthlyInvoice.apartment.building'])
            ->orderByDesc('first_overdue_date');

        if ($escalationStage) {
            $query->where('escalation_stage', $escalationStage);
        }

        if ($buildingId) {
            $query->whereHas('monthlyInvoice.apartment', fn ($q) => $q->where('building_id', $buildingId));
        }

        $flags = $query->get();
        $agreements = $this->agreementsForInvoices(
            $flags->pluck('monthlyInvoice')->filter()
        );

        $rows = $flags->map(function (DelinquencyFlag $flag) use ($agreements, $asOf) {
            $invoice = $flag->monthlyInvoice;
            if (! $invoice) {
                return null;
            }

            $agreement = $agreements->get($invoice->contract_id);
            $tenant = $agreement?->tenant;
            $building = $invoice->apartment?->building;
            $daysOverdue = $this->daysSinceFirstOverdue($flag->first_overdue_date, $asOf);

            return [
                'id' => $flag->id,
                'monthly_invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'first_overdue_date' => $flag->first_overdue_date?->toDateString(),
                'days_overdue' => $daysOverdue,
                'escalation_stage' => $flag->escalation_stage?->value,
                'escalation_label' => $flag->escalation_stage?->label(),
                'balance_due' => round((float) $invoice->balance_due, 2),
                'due_date' => $invoice->due_date?->toDateString(),
                'status' => $invoice->status,
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'display_name' => $tenant->full_display_name ?: null,
                    'tenant_code' => $tenant->tenant_code,
                ] : null,
                'building' => $building ? [
                    'id' => $building->id,
                    'name' => $building->name,
                ] : null,
            ];
        })->filter()->values()->all();

        $byStage = collect($rows)->groupBy('escalation_stage')->map->count();

        return [
            'as_of' => $asOf->toDateString(),
            'total' => count($rows),
            'counts_by_stage' => [
                'first_notice' => (int) ($byStage['first_notice'] ?? 0),
                'second_notice' => (int) ($byStage['second_notice'] ?? 0),
                'legal_handoff' => (int) ($byStage['legal_handoff'] ?? 0),
            ],
            'rows' => $rows,
        ];
    }

    public function escalationStageFor(Carbon|string $firstOverdueDate, Carbon $asOf): DelinquencyEscalationStage
    {
        $days = $this->daysSinceFirstOverdue($firstOverdueDate, $asOf);

        if ($days >= self::DAYS_TO_LEGAL_HANDOFF) {
            return DelinquencyEscalationStage::LegalHandoff;
        }

        if ($days >= self::DAYS_TO_SECOND_NOTICE) {
            return DelinquencyEscalationStage::SecondNotice;
        }

        return DelinquencyEscalationStage::FirstNotice;
    }

    public function firstOverdueDateFor(MonthlyInvoice $invoice): string
    {
        if (! $invoice->due_date) {
            return now()->toDateString();
        }

        return Carbon::parse($invoice->due_date)->addDay()->toDateString();
    }

    public function daysSinceFirstOverdue(Carbon|string $firstOverdueDate, Carbon $asOf): int
    {
        return max(0, (int) Carbon::parse($firstOverdueDate)->startOfDay()->diffInDays($asOf->startOfDay()));
    }

    /**
     * @param  Collection<int, MonthlyInvoice>  $invoices
     * @return Collection<string, Agreement>
     */
    protected function agreementsForInvoices(Collection $invoices): Collection
    {
        $agreementIds = $invoices
            ->where('contract_type', 'rental')
            ->pluck('contract_id')
            ->filter()
            ->unique()
            ->values();

        if ($agreementIds->isEmpty()) {
            return collect();
        }

        return Agreement::query()
            ->whereIn('id', $agreementIds)
            ->with('tenant')
            ->get()
            ->keyBy('id');
    }
}
