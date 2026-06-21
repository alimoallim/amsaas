<?php

namespace App\Services\Billing;

use App\Enums\MonthlyInvoiceStatus;
use App\Models\Agreement;
use App\Models\BillingItem;
use App\Models\Charge;
use App\Models\MonthlyInvoice;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use App\Services\Collections\DelinquencyTrackingService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceCreditNoteService
{
    public function issue(MonthlyInvoice $invoice, User $actor, string $reason): MonthlyInvoice
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => ['A credit note reason is required.'],
            ]);
        }

        $status = MonthlyInvoiceStatus::tryFrom($invoice->status);

        if (! $status || ! $this->isCreditable($status)) {
            throw ValidationException::withMessages([
                'status' => ['This invoice cannot receive a credit note in its current state.'],
            ]);
        }

        if ((float) $invoice->paid_amount > 0.009) {
            throw ValidationException::withMessages([
                'paid_amount' => ['Refund payments before issuing a credit note on this invoice.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor, $reason) {
            app(JournalEntryService::class)->postInvoiceCreditNote($invoice, $actor->id);

            PaymentAllocation::query()
                ->where('monthly_invoice_id', $invoice->id)
                ->delete();

            Charge::query()
                ->where('invoice_id', $invoice->id)
                ->update([
                    'invoice_id' => null,
                    'status' => Charge::STATUS_APPROVED,
                    'invoiced_at' => null,
                ]);

            BillingItem::query()
                ->where('invoice_id', $invoice->id)
                ->update([
                    'invoice_id' => null,
                    'posted_to_invoice' => false,
                    'status' => BillingItem::STATUS_PENDING,
                ]);

            $invoice->update([
                'status' => MonthlyInvoiceStatus::Cancelled->value,
                'paid_amount' => 0,
                'credit_note_number' => $this->generateCreditNoteNumber($invoice),
                'credit_note_reason' => $reason,
                'credit_noted_at' => now(),
                'credit_noted_by' => $actor->id,
            ]);

            $this->reapplyTenantCredits($invoice->fresh());
            app(DelinquencyTrackingService::class)->resolveForInvoice($invoice->fresh());

            return $invoice->fresh([
                'lineItems',
                'apartment.building',
                'allocations.payment',
            ]);
        });
    }

    public function isCreditable(MonthlyInvoiceStatus $status): bool
    {
        return in_array($status, [
            MonthlyInvoiceStatus::Issued,
            MonthlyInvoiceStatus::Finalized,
            MonthlyInvoiceStatus::Overdue,
        ], true);
    }

    protected function generateCreditNoteNumber(MonthlyInvoice $invoice): string
    {
        return sprintf('CN-%s-%s', now()->format('Ym'), Str::upper(Str::random(6)));
    }

    protected function reapplyTenantCredits(MonthlyInvoice $invoice): void
    {
        if ($invoice->contract_type !== 'rental') {
            return;
        }

        $agreement = Agreement::query()->find($invoice->contract_id);
        if (! $agreement?->tenant_id) {
            return;
        }

        app(PaymentService::class)->reapplyUnallocatedPayments(
            $invoice->company_id,
            $agreement->tenant_id,
        );
    }
}
