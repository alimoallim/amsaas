<?php

namespace App\Services\Accounting;

use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\DepositApplication;
use App\Models\SaleDepositApplication;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\SalePaymentAllocation;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalEntryService
{
    public function __construct(
        protected ChartOfAccountsService $chartOfAccounts,
        protected PostingRuleService $postingRules,
    ) {}

    public function postInvoiceIssued(MonthlyInvoice $invoice, ?string $userId = null): ?JournalEntry
    {
        $invoice->loadMissing('company');

        $existing = $this->findBySource(
            $invoice->company_id,
            JournalEntry::SOURCE_INVOICE_ISSUED,
            $invoice->id,
        );

        if ($existing) {
            return $existing;
        }

        $total = Money::toScale((string) ($invoice->total_amount ?? '0'));

        if (Money::comp($total, '0') <= 0) {
            return null;
        }

        $creditLines = $this->normalizeCreditLines(
            $this->postingRules->invoiceCreditLines($invoice),
            $total,
            $invoice->contract_type === 'sale'
                ? Account::CODE_SALE_INCOME
                : Account::CODE_RENTAL_INCOME,
            $invoice->invoice_number,
        );

        $entryDate = $invoice->issue_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $invoice->company_id,
            description: 'Invoice issued — '.$invoice->invoice_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_INVOICE_ISSUED,
            sourceId: $invoice->id,
            fiscalYear: $invoice->billing_year,
            fiscalMonth: $invoice->billing_month,
            currencyCode: $invoice->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => Account::CODE_ACCOUNTS_RECEIVABLE,
                'debit' => $total,
                'description' => 'Accounts receivable — '.$invoice->invoice_number,
            ]],
            creditLines: $creditLines,
            userId: $userId ?? $invoice->generated_by,
        );
    }

    public function postPaymentAllocation(PaymentAllocation $allocation, ?string $userId = null): ?JournalEntry
    {
        $allocation->loadMissing(['payment', 'monthlyInvoice.company']);

        $payment = $allocation->payment;
        $invoice = $allocation->monthlyInvoice;

        if (! $payment || ! $invoice) {
            return null;
        }

        $existing = $this->findBySource(
            $payment->company_id,
            JournalEntry::SOURCE_PAYMENT_ALLOCATION,
            $allocation->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $allocation->amount_allocated);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $entryDate = $payment->payment_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $payment->company_id,
            description: 'Payment allocated — '.$payment->receipt_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_PAYMENT_ALLOCATION,
            sourceId: $allocation->id,
            fiscalYear: (int) $payment->payment_date?->format('Y'),
            fiscalMonth: (int) $payment->payment_date?->format('n'),
            currencyCode: $invoice->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->resolveReceiptAccountCode($payment),
                'debit' => $amount,
                'description' => 'Receipt — '.$payment->receipt_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->accountsReceivableCode(),
                'credit' => $amount,
                'description' => 'AR reduction — '.$invoice->invoice_number,
            ]],
            userId: $userId ?? $payment->recorded_by,
        );
    }

    public function postSalePaymentAllocation(SalePaymentAllocation $allocation, ?string $userId = null): ?JournalEntry
    {
        $allocation->loadMissing(['payment', 'saleAgreement.agreement.company']);

        $payment = $allocation->payment;
        $sale = $allocation->saleAgreement;
        $agreement = $sale?->agreement;

        if (! $payment || ! $agreement) {
            return null;
        }

        $existing = $this->findBySource(
            $payment->company_id,
            JournalEntry::SOURCE_SALE_PAYMENT_ALLOCATION,
            $allocation->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $allocation->amount_allocated);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $entryDate = $payment->payment_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $payment->company_id,
            description: 'Sale payment — '.$payment->receipt_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_SALE_PAYMENT_ALLOCATION,
            sourceId: $allocation->id,
            fiscalYear: (int) $payment->payment_date?->format('Y'),
            fiscalMonth: (int) $payment->payment_date?->format('n'),
            currencyCode: $agreement->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->resolveReceiptAccountCode($payment),
                'debit' => $amount,
                'description' => 'Sale receipt — '.$payment->receipt_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->accountsReceivableCode(),
                'credit' => $amount,
                'description' => 'AR reduction — '.$agreement->agreement_number,
            ]],
            userId: $userId ?? $payment->recorded_by,
        );
    }

    public function postRentalSecurityDeposit(Payment $payment, ?string $userId = null): ?JournalEntry
    {
        $payment->loadMissing('company');

        $existing = $this->findBySource(
            $payment->company_id,
            JournalEntry::SOURCE_RENTAL_SECURITY_DEPOSIT,
            $payment->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $payment->amount);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $entryDate = $payment->payment_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $payment->company_id,
            description: 'Rental security deposit — '.$payment->receipt_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_RENTAL_SECURITY_DEPOSIT,
            sourceId: $payment->id,
            fiscalYear: (int) $payment->payment_date?->format('Y'),
            fiscalMonth: (int) $payment->payment_date?->format('n'),
            currencyCode: $payment->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->resolveReceiptAccountCode($payment),
                'debit' => $amount,
                'description' => 'Security deposit receipt — '.$payment->receipt_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->customerDepositsCode(),
                'credit' => $amount,
                'description' => 'Security deposit liability — '.$payment->receipt_number,
            ]],
            userId: $userId ?? $payment->recorded_by,
        );
    }

    public function postRentalDepositRefund(Payment $payment, ?string $userId = null): ?JournalEntry
    {
        $payment->loadMissing('company');

        $existing = $this->findBySource(
            $payment->company_id,
            JournalEntry::SOURCE_RENTAL_DEPOSIT_REFUND,
            $payment->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $payment->amount);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $entryDate = $payment->payment_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $payment->company_id,
            description: 'Rental deposit refund — '.$payment->receipt_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_RENTAL_DEPOSIT_REFUND,
            sourceId: $payment->id,
            fiscalYear: (int) $payment->payment_date?->format('Y'),
            fiscalMonth: (int) $payment->payment_date?->format('n'),
            currencyCode: $payment->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->customerDepositsCode(),
                'debit' => $amount,
                'description' => 'Security deposit liability release — '.$payment->receipt_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->resolveReceiptAccountCode($payment),
                'credit' => $amount,
                'description' => 'Deposit refund — '.$payment->receipt_number,
            ]],
            userId: $userId ?? $payment->recorded_by,
        );
    }

    public function postRentalDepositApplication(DepositApplication $application, ?string $userId = null): ?JournalEntry
    {
        $application->loadMissing(['monthlyInvoice.company', 'agreement']);

        $existing = $this->findBySource(
            $application->company_id,
            JournalEntry::SOURCE_RENTAL_DEPOSIT_APPLICATION,
            $application->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $application->amount);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $invoice = $application->monthlyInvoice;
        $entryDate = $invoice?->issue_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $application->company_id,
            description: 'Deposit applied — '.$invoice?->invoice_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_RENTAL_DEPOSIT_APPLICATION,
            sourceId: $application->id,
            fiscalYear: $invoice?->billing_year,
            fiscalMonth: $invoice?->billing_month,
            currencyCode: $invoice?->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->customerDepositsCode(),
                'debit' => $amount,
                'description' => 'Security deposit applied — '.$invoice?->invoice_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->accountsReceivableCode(),
                'credit' => $amount,
                'description' => 'AR reduction (deposit) — '.$invoice?->invoice_number,
            ]],
            userId: $userId,
        );
    }

    public function postSaleDepositApplication(SaleDepositApplication $application, ?string $userId = null): ?JournalEntry
    {
        $application->loadMissing(['saleAgreement.agreement.company', 'saleReservation']);

        $existing = $this->findBySource(
            $application->company_id,
            JournalEntry::SOURCE_SALE_DEPOSIT_APPLICATION,
            $application->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $application->amount);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $agreement = $application->saleAgreement?->agreement;
        $entryDate = now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $application->company_id,
            description: 'Sale deposit applied — '.$agreement?->agreement_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_SALE_DEPOSIT_APPLICATION,
            sourceId: $application->id,
            fiscalYear: (int) now()->format('Y'),
            fiscalMonth: (int) now()->format('n'),
            currencyCode: $agreement?->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->customerDepositsCode(),
                'debit' => $amount,
                'description' => 'Reservation deposit applied — '.$application->saleReservation?->reservation_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->accountsReceivableCode(),
                'credit' => $amount,
                'description' => 'AR reduction (sale deposit) — '.$agreement?->agreement_number,
            ]],
            userId: $userId,
        );
    }

    public function postCustomerDeposit(Payment $payment, ?string $userId = null): ?JournalEntry
    {
        $payment->loadMissing('company');

        $existing = $this->findBySource(
            $payment->company_id,
            JournalEntry::SOURCE_CUSTOMER_DEPOSIT,
            $payment->id,
        );

        if ($existing) {
            return $existing;
        }

        $amount = Money::toScale((string) $payment->amount);

        if (Money::comp($amount, '0') <= 0) {
            return null;
        }

        $entryDate = $payment->payment_date?->toDateString() ?? now()->toDateString();

        return $this->postBalancedEntry(
            companyId: $payment->company_id,
            description: 'Customer deposit — '.$payment->receipt_number,
            entryDate: $entryDate,
            postingDate: now()->toDateString(),
            sourceType: JournalEntry::SOURCE_CUSTOMER_DEPOSIT,
            sourceId: $payment->id,
            fiscalYear: (int) $payment->payment_date?->format('Y'),
            fiscalMonth: (int) $payment->payment_date?->format('n'),
            currencyCode: $payment->company?->currency_code ?? 'USD',
            debitLines: [[
                'account_code' => $this->postingRules->resolveReceiptAccountCode($payment),
                'debit' => $amount,
                'description' => 'Deposit receipt — '.$payment->receipt_number,
            ]],
            creditLines: [[
                'account_code' => $this->postingRules->customerDepositsCode(),
                'credit' => $amount,
                'description' => 'Customer deposit liability — '.$payment->receipt_number,
            ]],
            userId: $userId ?? $payment->recorded_by,
        );
    }

    /**
     * @return Collection<int, JournalEntry>
     */
    public function entriesForPayment(Payment $payment): Collection
    {
        $payment->loadMissing(['allocations', 'salePaymentAllocations']);

        $allocationIds = $payment->allocations->pluck('id')->filter()->values();
        $saleAllocationIds = $payment->salePaymentAllocations->pluck('id')->filter()->values();

        return JournalEntry::query()
            ->where('company_id', $payment->company_id)
            ->where(function ($query) use ($allocationIds, $saleAllocationIds, $payment) {
                if ($allocationIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($allocationIds) {
                        $inner->where('source_type', JournalEntry::SOURCE_PAYMENT_ALLOCATION)
                            ->whereIn('source_id', $allocationIds);
                    });
                }

                if ($saleAllocationIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($saleAllocationIds) {
                        $inner->where('source_type', JournalEntry::SOURCE_SALE_PAYMENT_ALLOCATION)
                            ->whereIn('source_id', $saleAllocationIds);
                    });
                }

                $query->orWhere(function ($inner) use ($payment) {
                    $inner->where('source_type', JournalEntry::SOURCE_CUSTOMER_DEPOSIT)
                        ->where('source_id', $payment->id);
                });

                $query->orWhere(function ($inner) use ($payment) {
                    $inner->where('source_type', JournalEntry::SOURCE_RENTAL_SECURITY_DEPOSIT)
                        ->where('source_id', $payment->id);
                });

                $query->orWhere(function ($inner) use ($payment) {
                    $inner->where('source_type', JournalEntry::SOURCE_RENTAL_DEPOSIT_REFUND)
                        ->where('source_id', $payment->id);
                });
            })
            ->with(['lines.account'])
            ->orderBy('entry_date')
            ->orderBy('entry_number')
            ->get();
    }

    /**
     * @param  array<int, array{account_code: string, debit?: string, credit?: string, description?: string}>  $debitLines
     * @param  array<int, array{account_code: string, debit?: string, credit?: string, description?: string}>  $creditLines
     */
    public function postBalancedEntry(
        string $companyId,
        string $description,
        string $entryDate,
        string $postingDate,
        string $sourceType,
        string $sourceId,
        ?int $fiscalYear,
        ?int $fiscalMonth,
        string $currencyCode,
        array $debitLines,
        array $creditLines,
        ?string $userId = null,
    ): JournalEntry {
        $totalDebit = Money::zero();
        $totalCredit = Money::zero();

        foreach ($debitLines as $line) {
            $totalDebit = Money::add($totalDebit, Money::toScale((string) ($line['debit'] ?? '0')));
        }

        foreach ($creditLines as $line) {
            $totalCredit = Money::add($totalCredit, Money::toScale((string) ($line['credit'] ?? '0')));
        }

        if (Money::comp($totalDebit, $totalCredit) !== 0) {
            throw new BusinessRuleException(
                'Journal entry is not balanced.',
                'JOURNAL_UNBALANCED',
            );
        }

        if (Money::comp($totalDebit, '0') <= 0) {
            throw new BusinessRuleException(
                'Journal entry amount must be greater than zero.',
                'JOURNAL_ZERO_AMOUNT',
            );
        }

        try {
            return DB::transaction(function () use (
                $companyId,
                $description,
                $entryDate,
                $postingDate,
                $sourceType,
                $sourceId,
                $fiscalYear,
                $fiscalMonth,
                $currencyCode,
                $debitLines,
                $creditLines,
                $totalDebit,
                $totalCredit,
                $userId,
            ) {
                $entry = JournalEntry::query()->create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'entry_number' => $this->nextEntryNumber($companyId),
                    'entry_date' => $entryDate,
                    'posting_date' => $postingDate,
                    'currency_code' => strtoupper($currencyCode),
                    'description' => $description,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'fiscal_year' => $fiscalYear,
                    'fiscal_month' => $fiscalMonth,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'status' => JournalEntry::STATUS_POSTED,
                    'created_by' => $userId,
                ]);

                $lineOrder = 0;

                foreach ($debitLines as $line) {
                    $this->createLine($entry, $companyId, $line, 'debit', $lineOrder++);
                }

                foreach ($creditLines as $line) {
                    $this->createLine($entry, $companyId, $line, 'credit', $lineOrder++);
                }

                return $entry->load('lines.account');
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateSource($exception)) {
                return $this->findBySource($companyId, $sourceType, $sourceId)
                    ?? throw $exception;
            }

            throw $exception;
        }
    }

    private function findBySource(string $companyId, string $sourceType, string $sourceId): ?JournalEntry
    {
        return JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->with('lines.account')
            ->first();
    }

    /**
     * @param  array<int, array{account_code: string, credit: string, description?: string}>  $creditLines
     * @return array<int, array{account_code: string, credit: string, description?: string}>
     */
    private function normalizeCreditLines(
        array $creditLines,
        string $total,
        string $fallbackAccountCode,
        string $invoiceNumber,
    ): array {
        $normalized = [];

        foreach ($creditLines as $line) {
            $amount = Money::toScale((string) $line['credit']);

            if (Money::comp($amount, '0') <= 0) {
                continue;
            }

            $normalized[] = [
                'account_code' => $line['account_code'],
                'credit' => $amount,
                'description' => $line['description'] ?? 'Invoice revenue — '.$invoiceNumber,
            ];
        }

        if ($normalized === []) {
            $normalized[] = [
                'account_code' => $fallbackAccountCode,
                'credit' => $total,
                'description' => 'Invoice revenue — '.$invoiceNumber,
            ];

            return $normalized;
        }

        return $this->reconcileCreditsToTotal($normalized, $total);
    }

    /**
     * @param  array<int, array{account_code: string, credit: string, description?: string}>  $creditLines
     * @return array<int, array{account_code: string, credit: string, description?: string}>
     */
    private function reconcileCreditsToTotal(array $creditLines, string $total): array
    {
        $sum = Money::zero();

        foreach ($creditLines as $line) {
            $sum = Money::add($sum, Money::toScale($line['credit']));
        }

        $delta = Money::sub($total, $sum);

        if (Money::comp($delta, '0') === 0) {
            return $creditLines;
        }

        $creditLines[0]['credit'] = Money::add(
            Money::toScale($creditLines[0]['credit']),
            $delta,
        );

        return $creditLines;
    }

    /**
     * @param  array{account_code: string, debit?: string, credit?: string, description?: string}  $line
     */
    private function createLine(
        JournalEntry $entry,
        string $companyId,
        array $line,
        string $side,
        int $order,
    ): void {
        $account = $this->chartOfAccounts->resolvePostingAccount($companyId, $line['account_code']);

        JournalEntryLine::query()->create([
            'id' => (string) Str::uuid(),
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit_amount' => $side === 'debit' ? Money::toScale((string) $line['debit']) : Money::zero(),
            'credit_amount' => $side === 'credit' ? Money::toScale((string) $line['credit']) : Money::zero(),
            'description' => $line['description'] ?? null,
            'line_order' => $order,
        ]);
    }

    private function nextEntryNumber(string $companyId): string
    {
        $count = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('entry_number', 'like', 'JE-'.now()->format('Ym').'-%')
            ->count();

        return sprintf('JE-%s-%04d', now()->format('Ym'), $count + 1);
    }

    private function isDuplicateSource(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'duplicate')
            || str_contains($message, 'unique')
            || str_contains($message, '23505');
    }
}
