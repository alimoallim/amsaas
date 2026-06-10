<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\DepositApplication;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\RentalAgreement;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use App\Services\Accounting\PostingRuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RentalDepositService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected JournalEntryService $journalEntryService,
    ) {}

    /**
     * @return array{required: float, received: float, refunded: float, applied: float, available: float}
     */
    public function summary(string $companyId, string $agreementId): array
    {
        $rental = RentalAgreement::query()->find($agreementId);

        $received = round((float) Payment::query()
            ->where('company_id', $companyId)
            ->where('agreement_id', $agreementId)
            ->where('payment_purpose', Payment::PURPOSE_SECURITY_DEPOSIT)
            ->where('status', 'completed')
            ->sum('amount'), 2);

        $refunded = round((float) Payment::query()
            ->where('company_id', $companyId)
            ->where('agreement_id', $agreementId)
            ->where('payment_purpose', Payment::PURPOSE_DEPOSIT_REFUND)
            ->where('status', 'completed')
            ->sum('amount'), 2);

        $applied = round((float) DepositApplication::query()
            ->where('company_id', $companyId)
            ->where('agreement_id', $agreementId)
            ->sum('amount'), 2);

        $available = round(max(0, $received - $refunded - $applied), 2);

        return [
            'required' => round((float) ($rental?->security_deposit ?? 0), 2),
            'received' => $received,
            'refunded' => $refunded,
            'applied' => $applied,
            'available' => $available,
        ];
    }

    /**
     * @param  array{tenant_id: string, agreement_id: string, amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $data
     */
    public function recordSecurityDeposit(User $user, array $data): Payment
    {
        return DB::transaction(function () use ($user, $data) {
            $amount = $this->positiveAmount($data['amount']);
            $agreement = $this->resolveRentalAgreement($user, $data['agreement_id'], $data['tenant_id']);

            $payment = Payment::create(array_merge([
                'company_id' => $user->company_id,
                'tenant_id' => $data['tenant_id'],
                'buyer_id' => null,
                'agreement_id' => $agreement->id,
                'receipt_number' => $this->generateReceiptNumber(),
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'completed',
                'payment_purpose' => Payment::PURPOSE_SECURITY_DEPOSIT,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->id,
            ], app(PostingRuleService::class)->receiptAccountAttributes($data)));

            $this->journalEntryService->postRentalSecurityDeposit($payment, $user->id);

            return $payment->load(['tenant', 'agreement']);
        });
    }

    /**
     * @param  array{tenant_id: string, agreement_id: string, amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $data
     */
    public function refundSecurityDeposit(User $user, array $data): Payment
    {
        return DB::transaction(function () use ($user, $data) {
            $amount = $this->positiveAmount($data['amount']);
            $agreement = $this->resolveRentalAgreement($user, $data['agreement_id'], $data['tenant_id']);

            $available = $this->summary($user->company_id, $agreement->id)['available'];
            if ($amount > $available + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => [sprintf(
                        'Refund exceeds available deposit balance ($%s).',
                        number_format($available, 2),
                    )],
                ]);
            }

            $payment = Payment::create(array_merge([
                'company_id' => $user->company_id,
                'tenant_id' => $data['tenant_id'],
                'buyer_id' => null,
                'agreement_id' => $agreement->id,
                'receipt_number' => $this->generateReceiptNumber(),
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'completed',
                'payment_purpose' => Payment::PURPOSE_DEPOSIT_REFUND,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->id,
            ], app(PostingRuleService::class)->receiptAccountAttributes($data)));

            $this->journalEntryService->postRentalDepositRefund($payment, $user->id);

            return $payment->load(['tenant', 'agreement']);
        });
    }

    /**
     * @param  array{monthly_invoice_id: string, amount: float|string, notes?: string}  $data
     */
    public function applyToInvoice(User $user, string $agreementId, array $data): DepositApplication
    {
        return DB::transaction(function () use ($user, $agreementId, $data) {
            $amount = $this->positiveAmount($data['amount']);
            $agreement = Agreement::query()
                ->where('company_id', $user->company_id)
                ->where('id', $agreementId)
                ->where('agreement_type', Agreement::TYPE_RENTAL)
                ->first();

            if (! $agreement) {
                throw ValidationException::withMessages([
                    'agreement_id' => ['Rental agreement not found.'],
                ]);
            }

            $available = $this->summary($user->company_id, $agreementId)['available'];
            if ($amount > $available + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => [sprintf(
                        'Application exceeds available deposit balance ($%s).',
                        number_format($available, 2),
                    )],
                ]);
            }

            $invoice = MonthlyInvoice::query()
                ->where('company_id', $user->company_id)
                ->where('contract_type', 'rental')
                ->where('contract_id', $agreementId)
                ->where('id', $data['monthly_invoice_id'])
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                throw ValidationException::withMessages([
                    'monthly_invoice_id' => ['Invoice does not belong to this rental agreement.'],
                ]);
            }

            if ((float) $invoice->balance_due <= 0.009) {
                throw ValidationException::withMessages([
                    'monthly_invoice_id' => ['Invoice has no balance due.'],
                ]);
            }

            $application = DepositApplication::query()->create([
                'company_id' => $user->company_id,
                'agreement_id' => $agreementId,
                'monthly_invoice_id' => $invoice->id,
                'amount' => round(min($amount, (float) $invoice->balance_due), 2),
                'applied_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->journalEntryService->postRentalDepositApplication($application, $user->id);
            $this->invoiceService->applyPayment($invoice, (float) $application->amount);

            return $application->load(['monthlyInvoice', 'agreement']);
        });
    }

    protected function resolveRentalAgreement(User $user, string $agreementId, string $tenantId): Agreement
    {
        $agreement = Agreement::query()
            ->where('company_id', $user->company_id)
            ->where('id', $agreementId)
            ->where('agreement_type', Agreement::TYPE_RENTAL)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $agreement) {
            throw ValidationException::withMessages([
                'agreement_id' => ['Select a rental agreement for this tenant.'],
            ]);
        }

        return $agreement;
    }

    protected function positiveAmount(float|string $amount): float
    {
        $value = round((float) $amount, 2);
        if ($value <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Amount must be greater than zero.'],
            ]);
        }

        return $value;
    }

    protected function generateReceiptNumber(): string
    {
        return sprintf('RCP-%s-%s', now()->format('Ym'), Str::upper(Str::random(8)));
    }
}
