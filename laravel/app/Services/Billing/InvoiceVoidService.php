<?php

namespace App\Services\Billing;

use App\Enums\MonthlyInvoiceStatus;
use App\Models\Agreement;
use App\Models\BillingItem;
use App\Models\Charge;
use App\Models\MonthlyInvoice;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Services\Collections\DelinquencyTrackingService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceVoidService
{
    public function void(MonthlyInvoice $invoice, User $actor, string $reason): MonthlyInvoice
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => ['A void reason is required.'],
            ]);
        }

        $status = MonthlyInvoiceStatus::tryFrom($invoice->status);

        if (! $status || ! $status->isVoidable()) {
            throw ValidationException::withMessages([
                'status' => ['This invoice cannot be voided in its current state.'],
            ]);
        }

        if ($status === MonthlyInvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'status' => ['Paid invoices cannot be voided. Issue a refund instead.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor, $reason) {
            $this->reversePaymentAllocations($invoice);
            $this->releaseSourceCharges($invoice);
            $this->releaseBillingItems($invoice);

            $invoice->update([
                'status' => MonthlyInvoiceStatus::Cancelled->value,
                'paid_amount' => 0,
                'void_reason' => $reason,
                'voided_at' => now(),
                'voided_by' => $actor->id,
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

    protected function reversePaymentAllocations(MonthlyInvoice $invoice): void
    {
        PaymentAllocation::query()
            ->where('monthly_invoice_id', $invoice->id)
            ->delete();
    }

    protected function releaseSourceCharges(MonthlyInvoice $invoice): void
    {
        Charge::query()
            ->where('invoice_id', $invoice->id)
            ->update([
                'invoice_id' => null,
                'status' => Charge::STATUS_APPROVED,
                'invoiced_at' => null,
            ]);
    }

    protected function releaseBillingItems(MonthlyInvoice $invoice): void
    {
        BillingItem::query()
            ->where('invoice_id', $invoice->id)
            ->update([
                'invoice_id' => null,
                'posted_to_invoice' => false,
                'status' => BillingItem::STATUS_PENDING,
            ]);
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
