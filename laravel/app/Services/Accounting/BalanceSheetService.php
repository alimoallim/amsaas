<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BalanceSheetService
{
    public function __construct(
        protected GeneralLedgerService $generalLedger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(User $user, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->endOfDay();

        $companyId = $user->company_id;
        $company = Company::query()->find($companyId);
        $epoch = Carbon::create(2000, 1, 1)->startOfDay();

        $assets = [];
        $liabilities = [];
        $equity = [];
        $totalAssets = Money::zero();
        $totalLiabilities = Money::zero();
        $totalEquity = Money::zero();

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('status', Account::STATUS_ACTIVE)
            ->whereIn('type', [Account::TYPE_ASSET, Account::TYPE_LIABILITY, Account::TYPE_EQUITY])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $signed = $this->generalLedger->balanceAsOf($account, $asOf);
            $balance = $this->sectionBalance($account->type, $signed);

            if (Money::comp($balance, '0') === 0) {
                continue;
            }

            $row = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => (float) $balance,
            ];

            match ($account->type) {
                Account::TYPE_ASSET => (function () use (&$assets, &$totalAssets, $row, $balance) {
                    $assets[] = $row;
                    $totalAssets = Money::add($totalAssets, $balance);
                })(),
                Account::TYPE_LIABILITY => (function () use (&$liabilities, &$totalLiabilities, $row, $balance) {
                    $liabilities[] = $row;
                    $totalLiabilities = Money::add($totalLiabilities, $balance);
                })(),
                Account::TYPE_EQUITY => (function () use (&$equity, &$totalEquity, $row, $balance) {
                    $equity[] = $row;
                    $totalEquity = Money::add($totalEquity, $balance);
                })(),
                default => null,
            };
        }

        $retainedEarnings = $this->retainedEarningsAsOf($companyId, $epoch, $asOf);

        if (Money::comp($retainedEarnings, '0') !== 0) {
            $equity[] = [
                'account_id' => null,
                'code' => '3900',
                'name' => 'Retained Earnings (computed)',
                'type' => Account::TYPE_EQUITY,
                'balance' => (float) $retainedEarnings,
                'is_computed' => true,
            ];
            $totalEquity = Money::add($totalEquity, $retainedEarnings);
        }

        $liabilitiesAndEquity = Money::add($totalLiabilities, $totalEquity);
        $variance = Money::sub($totalAssets, $liabilitiesAndEquity);
        $balanced = Money::comp($variance, '0') === 0;

        return [
            'as_of' => $asOf->toDateString(),
            'company' => [
                'id' => $company?->id,
                'name' => $company?->name,
                'currency_code' => $company?->currency_code ?? 'USD',
            ],
            'sections' => [
                'assets' => [
                    'rows' => $assets,
                    'total' => (float) $totalAssets,
                ],
                'liabilities' => [
                    'rows' => $liabilities,
                    'total' => (float) $totalLiabilities,
                ],
                'equity' => [
                    'rows' => $equity,
                    'total' => (float) $totalEquity,
                ],
            ],
            'totals' => [
                'assets' => (float) $totalAssets,
                'liabilities' => (float) $totalLiabilities,
                'equity' => (float) $totalEquity,
                'liabilities_and_equity' => (float) $liabilitiesAndEquity,
                'balanced' => $balanced,
                'variance' => (float) $variance,
            ],
        ];
    }

    public function pdfBinary(User $user, ?Carbon $asOf = null): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::info('DomPDF not installed; skipping balance sheet PDF.');

            return null;
        }

        $report = $this->report($user, $asOf);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.balance_sheet', [
            'report' => $report,
        ]);

        return $pdf->output();
    }

    private function retainedEarningsAsOf(string $companyId, Carbon $from, Carbon $asOf): string
    {
        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('status', Account::STATUS_ACTIVE)
            ->whereIn('type', [Account::TYPE_REVENUE, Account::TYPE_EXPENSE])
            ->get();

        $net = Money::zero();

        foreach ($accounts as $account) {
            $signed = $this->generalLedger->closingBalanceForPeriod($account, $from, $asOf);

            if ($account->type === Account::TYPE_REVENUE) {
                $net = Money::add($net, $signed);
            } else {
                $net = Money::sub($net, $signed);
            }
        }

        return $net;
    }

    private function sectionBalance(string $accountType, string $signedBalance): string
    {
        if (Money::comp($signedBalance, '0') === 0) {
            return Money::zero();
        }

        $isDebitNormal = in_array($accountType, [Account::TYPE_ASSET], true);
        $positive = Money::comp($signedBalance, '0') > 0;

        if (($isDebitNormal && $positive) || (! $isDebitNormal && ! $positive)) {
            return $this->absAmount($signedBalance);
        }

        return Money::zero();
    }

    private function absAmount(string $amount): string
    {
        return Money::comp($amount, '0') < 0
            ? Money::sub(Money::zero(), $amount)
            : $amount;
    }
}
