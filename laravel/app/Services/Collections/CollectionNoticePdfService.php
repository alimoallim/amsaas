<?php

namespace App\Services\Collections;

use App\Enums\DelinquencyEscalationStage;
use App\Models\Agreement;
use App\Models\CollectionNotice;
use App\Models\Company;
use App\Models\DelinquencyFlag;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollectionNoticePdfService
{
    public function generateForFlag(DelinquencyFlag $flag, User $actor): CollectionNotice
    {
        if ($flag->resolved_at !== null) {
            throw ValidationException::withMessages([
                'flag_id' => ['This delinquency flag is already resolved.'],
            ]);
        }

        $invoice = $flag->monthlyInvoice;
        if (! $invoice) {
            throw ValidationException::withMessages([
                'flag_id' => ['Linked invoice not found.'],
            ]);
        }

        $noticeType = $flag->escalation_stage ?? DelinquencyEscalationStage::FirstNotice;

        $existing = CollectionNotice::query()
            ->where('delinquency_flag_id', $flag->id)
            ->where('notice_type', $noticeType->value)
            ->first();

        if ($existing && Storage::disk('local')->exists($existing->file_path)) {
            return $existing;
        }

        $path = $this->renderPdf($flag, $invoice, $noticeType);
        if (! $path) {
            throw ValidationException::withMessages([
                'flag_id' => ['PDF generation is unavailable. Ensure DomPDF is installed.'],
            ]);
        }

        if ($existing) {
            $existing->update([
                'file_path' => $path,
                'generated_by' => $actor->id,
            ]);

            return $existing->fresh();
        }

        return CollectionNotice::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $flag->company_id,
            'delinquency_flag_id' => $flag->id,
            'monthly_invoice_id' => $invoice->id,
            'notice_type' => $noticeType,
            'file_path' => $path,
            'generated_by' => $actor->id,
        ]);
    }

    protected function renderPdf(
        DelinquencyFlag $flag,
        MonthlyInvoice $invoice,
        DelinquencyEscalationStage $noticeType,
    ): ?string {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::warning('DomPDF not installed; skipping collection notice generation.');

            return null;
        }

        $invoice->loadMissing(['apartment.building']);
        $company = Company::query()->find($invoice->company_id);
        $tenantName = $this->resolveTenantName($invoice);
        $copy = $this->noticeCopy($noticeType);

        $daysOverdue = app(DelinquencyTrackingService::class)
            ->daysSinceFirstOverdue($flag->first_overdue_date, now()->startOfDay());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('collections.notice', [
            'company' => $company,
            'invoice' => $invoice,
            'tenantName' => $tenantName,
            'flag' => $flag,
            'noticeType' => $noticeType,
            'noticeTitle' => $copy['title'],
            'noticeHeading' => $copy['heading'],
            'noticeBody' => $copy['body'],
            'daysOverdue' => $daysOverdue,
            'generatedAt' => now()->format('Y-m-d'),
        ]);

        $path = "collection-notices/{$invoice->company_id}/{$flag->id}-{$noticeType->value}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * @return array{title: string, heading: string, body: string}
     */
    protected function noticeCopy(DelinquencyEscalationStage $stage): array
    {
        return match ($stage) {
            DelinquencyEscalationStage::FirstNotice => [
                'title' => 'First Payment Reminder',
                'heading' => 'Friendly payment reminder',
                'body' => "Our records show that your account has an outstanding balance past the due date.\n\n"
                    ."This is a first reminder to settle the amount due. If payment has already been made, please disregard this notice and accept our thanks.",
            ],
            DelinquencyEscalationStage::SecondNotice => [
                'title' => 'Formal Demand for Payment',
                'heading' => 'Second notice — immediate action required',
                'body' => "Despite our previous reminder, your account remains overdue.\n\n"
                    ."This is a formal demand for payment of the full outstanding balance within seven (7) days of the date of this notice. Failure to pay may result in further collection action.",
            ],
            DelinquencyEscalationStage::LegalHandoff => [
                'title' => 'Notice of Legal Handoff',
                'heading' => 'Final notice before legal proceedings',
                'body' => "Your account is seriously overdue and has been flagged for legal handoff.\n\n"
                    ."Unless the full outstanding balance is received within five (5) days, your file may be referred to legal counsel for recovery of rent and associated costs.",
            ],
        };
    }

    protected function resolveTenantName(MonthlyInvoice $invoice): string
    {
        if ($invoice->contract_type !== 'rental') {
            return 'Tenant';
        }

        $agreement = Agreement::query()
            ->with('tenant:id,display_name,first_name,last_name')
            ->find($invoice->contract_id);

        $tenant = $agreement?->tenant;
        if (! $tenant) {
            return 'Tenant';
        }

        $display = trim((string) ($tenant->display_name ?? ''));

        return $display !== ''
            ? $display
            : (trim(collect([$tenant->first_name, $tenant->last_name])->filter()->implode(' ')) ?: 'Tenant');
    }
}
