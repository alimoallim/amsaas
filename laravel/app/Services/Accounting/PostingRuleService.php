<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\ChargeType;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Models\Payment;

class PostingRuleService
{
    /** @var array<string, string> */
    private const PAYMENT_METHOD_RECEIPT_ACCOUNTS = [
        'cash' => Account::CODE_CASH,
        'bank_transfer' => Account::CODE_BANK,
        'mobile_money' => Account::CODE_MOBILE_MONEY,
        'cheque' => Account::CODE_CHEQUE_IN_TRANSIT,
    ];

    /** @var array<int, string> */
    public const RECEIPT_ACCOUNT_CODES = [
        Account::CODE_CASH,
        Account::CODE_BANK,
        Account::CODE_MOBILE_MONEY,
        Account::CODE_CHEQUE_IN_TRANSIT,
    ];

    /** @var array<string, string> */
    private const INVOICE_BUCKET_DEFAULTS = [
        'rent' => Account::CODE_RENTAL_INCOME,
        'utilities' => Account::CODE_UTILITY_INCOME,
        'services' => Account::CODE_SERVICE_INCOME,
        'installment' => Account::CODE_SALE_INCOME,
    ];

    /** @var array<string, string> */
    private const CHARGE_CATEGORY_BUCKETS = [
        ChargeType::CATEGORY_RENT => 'rent',
        ChargeType::CATEGORY_UTILITY => 'utilities',
        ChargeType::CATEGORY_SERVICE => 'services',
        ChargeType::CATEGORY_MISCELLANEOUS => 'services',
        ChargeType::CATEGORY_PENALTY => 'services',
        ChargeType::CATEGORY_TAX => 'services',
    ];

    public function receiptAccountCode(?string $paymentMethod): string
    {
        $method = strtolower(trim((string) $paymentMethod));

        return self::PAYMENT_METHOD_RECEIPT_ACCOUNTS[$method] ?? Account::CODE_CASH;
    }

    public function resolveReceiptAccountCode(Payment $payment): string
    {
        if (filled($payment->receipt_account_code)) {
            return (string) $payment->receipt_account_code;
        }

        return $this->receiptAccountCode($payment->payment_method);
    }

    public function isReceiptAccountCode(?string $code): bool
    {
        return in_array((string) $code, self::RECEIPT_ACCOUNT_CODES, true);
    }

    /**
     * @return array{receipt_account_code: ?string}
     */
    public function receiptAccountAttributes(array $data): array
    {
        $override = $data['receipt_account_code'] ?? null;

        if (! filled($override)) {
            return ['receipt_account_code' => null];
        }

        $default = $this->receiptAccountCode($data['payment_method'] ?? null);

        return [
            'receipt_account_code' => $override === $default ? null : (string) $override,
        ];
    }

    public function receiptAccountName(string $companyId, string $accountCode): ?string
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->where('code', $accountCode)
            ->value('name');
    }

    /**
     * @return array<int, array{code: string, name: string, default_for_methods: array<int, string>}>
     */
    public function receiptAccountOptions(string $companyId): array
    {
        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->whereIn('code', self::RECEIPT_ACCOUNT_CODES)
            ->orderBy('code')
            ->get(['code', 'name']);

        $methodsByCode = [];

        foreach (self::PAYMENT_METHOD_RECEIPT_ACCOUNTS as $method => $code) {
            $methodsByCode[$code][] = $method;
        }

        return $accounts
            ->map(fn (Account $account) => [
                'code' => $account->code,
                'name' => $account->name,
                'default_for_methods' => $methodsByCode[$account->code] ?? [],
            ])
            ->values()
            ->all();
    }

    public function accountsReceivableCode(): string
    {
        return Account::CODE_ACCOUNTS_RECEIVABLE;
    }

    public function customerDepositsCode(): string
    {
        return Account::CODE_CUSTOMER_DEPOSITS_PAYABLE;
    }

    /**
     * @return array<int, array{account_code: string, credit: string, description: string}>
     */
    public function invoiceCreditLines(MonthlyInvoice $invoice): array
    {
        if ($invoice->contract_type === 'sale') {
            return $this->saleInvoiceCreditLines($invoice);
        }

        return $this->rentalInvoiceCreditLines($invoice);
    }

    /**
     * @return array<int, array{account_code: string, credit: string, description: string}>
     */
    private function rentalInvoiceCreditLines(MonthlyInvoice $invoice): array
    {
        $invoice->loadMissing(['lineItems.chargeType']);

        if ($invoice->lineItems->isNotEmpty()) {
            $lines = $this->rentalCreditLinesFromLineItems($invoice);

            if ($lines !== []) {
                return $lines;
            }
        }

        return $this->rentalInvoiceCreditLinesFromBuckets($invoice);
    }

    /**
     * @return array<int, array{account_code: string, credit: string, description: string}>
     */
    private function rentalCreditLinesFromLineItems(MonthlyInvoice $invoice): array
    {
        $invoiceNumber = (string) $invoice->invoice_number;
        $companyId = (string) $invoice->company_id;
        $draftLines = [];

        foreach ($invoice->lineItems as $line) {
            $amount = (string) $line->amount;

            if (bccomp($amount, '0', 4) <= 0) {
                continue;
            }

            $bucket = $this->bucketForLineItem($line);
            $accountCode = $this->revenueAccountForLineItem($companyId, $line);

            $draftLines[] = [
                'account_code' => $accountCode,
                'credit' => $amount,
                'bucket' => $bucket,
                'description' => $this->lineItemDescription($line, $invoiceNumber),
            ];
        }

        if ($draftLines === []) {
            return [];
        }

        $draftLines = $this->applyDiscountToRentLines(
            $draftLines,
            (string) ($invoice->discount_amount ?? '0'),
        );

        return $this->groupCreditLines($draftLines);
    }

    /**
     * @param  array<int, array{account_code: string, credit: string, bucket: string, description: string}>  $lines
     * @return array<int, array{account_code: string, credit: string, bucket: string, description: string}>
     */
    private function applyDiscountToRentLines(array $lines, string $discount): array
    {
        if (bccomp($discount, '0', 4) <= 0) {
            return $lines;
        }

        $rentTotal = '0';

        foreach ($lines as $line) {
            if ($line['bucket'] === 'rent') {
                $rentTotal = bcadd($rentTotal, $line['credit'], 4);
            }
        }

        if (bccomp($rentTotal, '0', 4) <= 0) {
            return $lines;
        }

        $discountToApply = bccomp($discount, $rentTotal, 4) <= 0 ? $discount : $rentTotal;
        $remaining = $discountToApply;

        foreach ($lines as $index => $line) {
            if ($line['bucket'] !== 'rent' || bccomp($remaining, '0', 4) <= 0) {
                continue;
            }

            $share = bccomp($remaining, $line['credit'], 4) <= 0
                ? $remaining
                : $line['credit'];

            $lines[$index]['credit'] = bcsub($line['credit'], $share, 4);
            $remaining = bcsub($remaining, $share, 4);
        }

        return array_values(array_filter(
            $lines,
            fn (array $line) => bccomp($line['credit'], '0', 4) > 0,
        ));
    }

    /**
     * @param  array<int, array{account_code: string, credit: string, description: string}>  $lines
     * @return array<int, array{account_code: string, credit: string, description: string}>
     */
    private function groupCreditLines(array $lines): array
    {
        $grouped = [];

        foreach ($lines as $line) {
            $code = $line['account_code'];

            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'account_code' => $code,
                    'credit' => '0',
                    'description' => $line['description'],
                ];
            }

            $grouped[$code]['credit'] = bcadd($grouped[$code]['credit'], $line['credit'], 4);
        }

        return array_values($grouped);
    }

    /**
     * @return array<int, array{account_code: string, credit: string, description: string}>
     */
    private function rentalInvoiceCreditLinesFromBuckets(MonthlyInvoice $invoice): array
    {
        $lines = [];
        $invoiceNumber = (string) $invoice->invoice_number;

        $buckets = [
            'rent' => (string) ($invoice->subtotal_rent ?? '0'),
            'utilities' => (string) ($invoice->subtotal_utilities ?? '0'),
            'services' => (string) ($invoice->subtotal_services ?? '0'),
        ];

        $discount = (string) ($invoice->discount_amount ?? '0');
        if (bccomp($discount, '0', 4) > 0 && bccomp($buckets['rent'], '0', 4) > 0) {
            $buckets['rent'] = bcsub(
                $buckets['rent'],
                bccomp($discount, $buckets['rent'], 4) <= 0 ? $discount : $buckets['rent'],
                4,
            );
        }

        foreach ($buckets as $bucket => $amount) {
            if (bccomp($amount, '0', 4) <= 0) {
                continue;
            }

            $lines[] = [
                'account_code' => $this->revenueAccountForBucket($invoice->company_id, $bucket),
                'credit' => $amount,
                'description' => $this->bucketDescription($bucket, $invoiceNumber),
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, array{account_code: string, credit: string, description: string}>
     */
    private function saleInvoiceCreditLines(MonthlyInvoice $invoice): array
    {
        $amount = (string) ($invoice->subtotal_installment ?? $invoice->total_amount ?? '0');

        if (bccomp($amount, '0', 4) <= 0) {
            $amount = (string) ($invoice->total_amount ?? '0');
        }

        return [[
            'account_code' => Account::CODE_SALE_INCOME,
            'credit' => $amount,
            'description' => 'Property sale revenue — '.$invoice->invoice_number,
        ]];
    }

    private function revenueAccountForLineItem(string $companyId, InvoiceLineItem $line): string
    {
        $line->loadMissing('chargeType');

        if ($line->chargeType) {
            $ledgerCode = $line->chargeType->ledger_account_code;

            if (is_string($ledgerCode) && trim($ledgerCode) !== '') {
                return strtoupper(trim($ledgerCode));
            }

            $bucket = $this->categoryToBucket($line->chargeType->category);

            return $this->revenueAccountForBucket($companyId, $bucket);
        }

        return $this->revenueAccountForBucket(
            $companyId,
            $this->bucketForLineType((string) $line->line_type),
        );
    }

    private function bucketForLineItem(InvoiceLineItem $line): string
    {
        $line->loadMissing('chargeType');

        if ($line->chargeType?->category) {
            return $this->categoryToBucket($line->chargeType->category);
        }

        return $this->bucketForLineType((string) $line->line_type);
    }

    private function categoryToBucket(?string $category): string
    {
        if ($category === null) {
            return 'services';
        }

        return self::CHARGE_CATEGORY_BUCKETS[$category] ?? 'services';
    }

    private function bucketForLineType(string $lineType): string
    {
        return match ($lineType) {
            'rent' => 'rent',
            'utility', 'electricity', 'water', 'gas' => 'utilities',
            'installment' => 'installment',
            default => 'services',
        };
    }

    private function revenueAccountForBucket(string $companyId, string $bucket): string
    {
        $fallback = self::INVOICE_BUCKET_DEFAULTS[$bucket] ?? Account::CODE_RENTAL_INCOME;
        $category = array_search($bucket, self::CHARGE_CATEGORY_BUCKETS, true);

        if ($category === false) {
            return $fallback;
        }

        $ledgerCode = ChargeType::query()
            ->where('company_id', $companyId)
            ->where('category', $category)
            ->where('status', ChargeType::STATUS_ACTIVE)
            ->whereNotNull('ledger_account_code')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('ledger_account_code');

        if (! is_string($ledgerCode) || trim($ledgerCode) === '') {
            return $fallback;
        }

        return strtoupper(trim($ledgerCode));
    }

    private function lineItemDescription(InvoiceLineItem $line, string $invoiceNumber): string
    {
        $label = $line->chargeType?->name
            ?? $line->description
            ?? $line->line_type;

        return sprintf('%s — %s', $label, $invoiceNumber);
    }

    private function bucketDescription(string $bucket, string $invoiceNumber): string
    {
        return match ($bucket) {
            'rent' => 'Rental income — '.$invoiceNumber,
            'utilities' => 'Utility recovery — '.$invoiceNumber,
            'services' => 'Service charges — '.$invoiceNumber,
            default => 'Invoice revenue — '.$invoiceNumber,
        };
    }
}
