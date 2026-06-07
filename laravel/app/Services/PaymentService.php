<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Record a tenant payment and allocate FIFO to open rental invoices.
     *
     * @param  array{tenant_id: string, amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $data
     */
    public function recordPayment(User $user, array $data): Payment
    {
        return DB::transaction(function () use ($user, $data) {
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount must be greater than zero.'],
                ]);
            }

            $this->reapplyUnallocatedPayments($user->company_id, $data['tenant_id']);

            $payment = Payment::create([
                'company_id' => $user->company_id,
                'tenant_id' => $data['tenant_id'],
                'buyer_id' => null,
                'receipt_number' => $this->generateReceiptNumber(),
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->id,
            ]);

            $this->allocateToOpenInvoices($user->company_id, $data['tenant_id'], $payment, $amount);

            return $payment->load(['allocations.monthlyInvoice', 'tenant']);
        });
    }

    /**
     * Apply leftover amounts from prior payments when new invoice balance opens.
     */
    public function reapplyUnallocatedPayments(string $companyId, string $tenantId): void
    {
        DB::transaction(function () use ($companyId, $tenantId) {
            $payments = Payment::query()
                ->where('company_id', $companyId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->with('allocations')
                ->orderBy('payment_date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                $remaining = $this->unallocatedAmount($payment);
                if ($remaining <= 0.009) {
                    continue;
                }

                $this->allocateToOpenInvoices($companyId, $tenantId, $payment, $remaining);
            }
        });
    }

    /**
     * @return array{allocated: float, unallocated: float}
     */
    public function allocationSummary(Payment $payment): array
    {
        $payment->loadMissing('allocations');

        $allocated = round((float) $payment->allocations->sum('amount_allocated'), 2);
        $unallocated = round(max(0, (float) $payment->amount - $allocated), 2);

        return [
            'allocated' => $allocated,
            'unallocated' => $unallocated,
        ];
    }

    public function resultMessage(Payment $payment): string
    {
        ['allocated' => $allocated, 'unallocated' => $unallocated] = $this->allocationSummary($payment);

        if ($unallocated <= 0.009) {
            return 'Payment recorded and fully allocated to open invoices.';
        }

        if ($allocated <= 0.009) {
            return 'Payment recorded but nothing allocated — no open issued invoice balance. Amount is held as tenant credit until an invoice is issued or balance opens.';
        }

        return sprintf(
            'Payment recorded. $%s allocated to open invoices; $%s held as tenant credit (no remaining open balance).',
            number_format($allocated, 2),
            number_format($unallocated, 2),
        );
    }

    protected function allocateToOpenInvoices(
        string $companyId,
        string $tenantId,
        Payment $payment,
        float $amount,
    ): float {
        $remaining = round($amount, 2);
        if ($remaining <= 0) {
            return 0;
        }

        $agreementIds = Agreement::query()
            ->where('company_id', $companyId)
            ->where('tenant_id', $tenantId)
            ->pluck('id');

        if ($agreementIds->isEmpty()) {
            return $remaining;
        }

        $invoices = MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('contract_type', 'rental')
            ->whereIn('contract_id', $agreementIds)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid', 'overdue'])
            ->whereRaw('balance_due > 0')
            ->orderBy('issue_date')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($invoices as $invoice) {
            if ($remaining <= 0.009) {
                break;
            }

            $invoice->refresh();
            $balance = (float) $invoice->balance_due;
            if ($balance <= 0.009) {
                continue;
            }

            $allocationAmount = round(min($remaining, $balance), 2);
            if ($allocationAmount <= 0) {
                continue;
            }

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'monthly_invoice_id' => $invoice->id,
                'amount_allocated' => $allocationAmount,
            ]);

            $this->invoiceService->applyPayment($invoice, $allocationAmount);
            $remaining = round($remaining - $allocationAmount, 2);
        }

        return max(0, $remaining);
    }

    protected function unallocatedAmount(Payment $payment): float
    {
        $allocated = (float) $payment->allocations->sum('amount_allocated');

        return round(max(0, (float) $payment->amount - $allocated), 2);
    }

    protected function generateReceiptNumber(): string
    {
        return sprintf('RCP-%s-%s', now()->format('Ym'), Str::upper(Str::random(8)));
    }
}
