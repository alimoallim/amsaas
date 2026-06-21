<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentRefundService
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * @param  list<string>|null  $allocationIds
     */
    public function refund(
        User $actor,
        Payment $payment,
        float $amount,
        string $reason,
        ?array $allocationIds = null,
    ): Payment {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => ['A refund reason is required.'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Refund amount must be greater than zero.'],
            ]);
        }

        if (($payment->payment_purpose ?? Payment::PURPOSE_RENT) !== Payment::PURPOSE_RENT) {
            throw ValidationException::withMessages([
                'payment_purpose' => ['Only rent payments can be refunded through this workflow.'],
            ]);
        }

        if (! in_array($payment->status, ['completed', 'partially_refunded'], true)) {
            throw ValidationException::withMessages([
                'status' => ['This payment cannot be refunded in its current state.'],
            ]);
        }

        $refundable = round((float) $payment->amount - (float) ($payment->refunded_amount ?? 0), 2);
        if ($amount > $refundable + 0.009) {
            throw ValidationException::withMessages([
                'amount' => ['Refund amount exceeds the remaining refundable balance.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $payment, $amount, $reason, $allocationIds) {
            $remaining = round($amount, 2);
            $allocations = $this->allocationsToReverse($payment, $allocationIds);

            foreach ($allocations as $allocation) {
                if ($remaining <= 0.009) {
                    break;
                }

                $reverseAmount = round(min($remaining, (float) $allocation->amount_allocated), 2);
                if ($reverseAmount <= 0) {
                    continue;
                }

                if ($reverseAmount + 0.009 < (float) $allocation->amount_allocated) {
                    throw ValidationException::withMessages([
                        'amount' => ['Partial allocation refunds are not supported. Refund full allocation amounts or specify allocation IDs.'],
                    ]);
                }

                app(JournalEntryService::class)->postPaymentAllocationReversal(
                    $allocation,
                    $actor->id,
                );

                $invoice = $allocation->monthlyInvoice;
                if ($invoice) {
                    $this->invoiceService->reversePayment($invoice, $reverseAmount);
                }

                $allocation->delete();
                $remaining = round($remaining - $reverseAmount, 2);
            }

            if ($remaining > 0.009) {
                $unallocated = $this->unallocatedAmount($payment->fresh(['allocations']));

                if ($remaining > $unallocated + 0.009) {
                    throw ValidationException::withMessages([
                        'amount' => ['Refund amount exceeds refundable balances on this payment.'],
                    ]);
                }

                app(JournalEntryService::class)->postPaymentUnallocatedRefund(
                    $payment->fresh(),
                    (string) $remaining,
                    $actor->id,
                    (string) Str::uuid(),
                );
            }

            $newRefunded = round((float) ($payment->refunded_amount ?? 0) + $amount, 2);
            $fullyRefunded = $newRefunded + 0.009 >= (float) $payment->amount;

            $payment->update([
                'refunded_amount' => $newRefunded,
                'refund_reason' => $reason,
                'refunded_at' => now(),
                'refunded_by' => $actor->id,
                'status' => $fullyRefunded ? 'refunded' : 'partially_refunded',
            ]);

            return $payment->fresh(['allocations.monthlyInvoice', 'tenant', 'recordedBy']);
        });
    }

    public function refundableAmount(Payment $payment): float
    {
        return round(max(0, (float) $payment->amount - (float) ($payment->refunded_amount ?? 0)), 2);
    }

    public function canRefund(Payment $payment): bool
    {
        return ($payment->payment_purpose ?? Payment::PURPOSE_RENT) === Payment::PURPOSE_RENT
            && in_array($payment->status, ['completed', 'partially_refunded'], true)
            && $this->refundableAmount($payment) > 0.009;
    }

    /**
     * @param  list<string>|null  $allocationIds
     * @return \Illuminate\Support\Collection<int, PaymentAllocation>
     */
    protected function unallocatedAmount(Payment $payment): float
    {
        $payment->loadMissing('allocations');
        $allocated = round((float) $payment->allocations->sum('amount_allocated'), 2);

        return round(max(0, (float) $payment->amount - $allocated), 2);
    }

    /**
     * @param  list<string>|null  $allocationIds
     * @return \Illuminate\Support\Collection<int, PaymentAllocation>
     */
    protected function allocationsToReverse(Payment $payment, ?array $allocationIds)
    {
        $query = PaymentAllocation::query()
            ->where('payment_id', $payment->id)
            ->with('monthlyInvoice')
            ->orderByDesc('created_at');

        if ($allocationIds !== null && $allocationIds !== []) {
            $query->whereIn('id', $allocationIds);
        }

        return $query->get();
    }
}
