<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Events\InvoiceIssued;
use App\Services\Accounting\JournalEntryService;
use App\Services\Billing\InvoicePdfService;
use App\Services\Collections\DelinquencyTrackingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /**
     * Issues an invoice, locking it from further editing.
     */
    public function issue(MonthlyInvoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft invoices can be issued.']);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => 'issued',
                'finalized_at' => now(),
            ]);

            $fresh = $invoice->fresh();

            app(JournalEntryService::class)->postInvoiceIssued(
                $fresh,
                auth()->id(),
            );

            $this->reapplyTenantCredits($fresh);

            DB::afterCommit(function () use ($invoice) {
                $fresh = $invoice->fresh();
                app(InvoicePdfService::class)->ensureReady($fresh);
                event(new InvoiceIssued($fresh->fresh()));
            });
        });
    }

    /**
     * Applies a payment against an invoice.
     */
    public function applyPayment(MonthlyInvoice $invoice, float $amount): void
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            throw ValidationException::withMessages(['status' => 'Cannot apply payment to this invoice.']);
        }

        $invoice->refresh();
        $balance = (float) $invoice->balance_due;
        if ($balance <= 0.009) {
            return;
        }

        $appliedAmount = round(min($amount, $balance), 2);
        if ($appliedAmount <= 0) {
            return;
        }

        $newPaidAmount = round((float) $invoice->paid_amount + $appliedAmount, 2);

        $invoice->update(['paid_amount' => $newPaidAmount]);
        $invoice->refresh();

        $isPaid = (float) $invoice->balance_due <= 0.009;
        $invoice->update(['status' => $isPaid ? 'paid' : 'partially_paid']);

        if ($isPaid) {
            app(DelinquencyTrackingService::class)->resolveForInvoice($invoice->fresh());
        }
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

        app(PaymentService::class)->reapplyUnallocatedPayments($invoice->company_id, $agreement->tenant_id);
    }

    /**
     * Reverses a prior payment allocation against an invoice.
     */
    public function reversePayment(MonthlyInvoice $invoice, float $amount): void
    {
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => 'Cannot reverse payment on a cancelled invoice.']);
        }

        $invoice->refresh();
        $reversal = round(min($amount, (float) $invoice->paid_amount), 2);
        if ($reversal <= 0) {
            return;
        }

        $newPaidAmount = round((float) $invoice->paid_amount - $reversal, 2);
        $invoice->update(['paid_amount' => $newPaidAmount]);
        $invoice->refresh();

        if ((float) $invoice->balance_due <= 0.009) {
            $invoice->update(['status' => 'paid']);

            return;
        }

        if ($newPaidAmount <= 0.009) {
            $status = $invoice->due_date && $invoice->due_date->isPast() ? 'overdue' : 'issued';
            $invoice->update(['status' => $status]);

            return;
        }

        $invoice->update(['status' => 'partially_paid']);
    }

    /**
     * Cancels an invoice.
     */
    public function cancel(MonthlyInvoice $invoice, string $reason): void
    {
        if ($invoice->status === 'paid') {
            throw ValidationException::withMessages(['status' => 'Cannot cancel an already paid invoice.']);
        }

        $invoice->update([
            'status' => 'cancelled',
        ]);
        
        // Optionally log the cancellation reason in a separate audit table
    }
}