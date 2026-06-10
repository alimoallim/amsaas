<?php

namespace App\Services\Accounting;

use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\AccountingPeriodClose;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrialBalanceService
{
    public function __construct(
        protected GeneralLedgerService $generalLedger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = ($from ?? now()->startOfMonth())->copy()->startOfDay();
        $to = ($to ?? now())->copy()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $companyId = $user->company_id;
        $activity = $this->periodActivity($companyId, $from, $to);

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('status', Account::STATUS_ACTIVE)
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalBalanceDebit = Money::zero();
        $totalBalanceCredit = Money::zero();
        $totalActivityDebit = Money::zero();
        $totalActivityCredit = Money::zero();

        foreach ($accounts as $account) {
            $stats = $activity[$account->id] ?? [
                'period_debits' => Money::zero(),
                'period_credits' => Money::zero(),
            ];

            $periodDebits = $stats['period_debits'];
            $periodCredits = $stats['period_credits'];
            $closingSigned = $this->generalLedger->closingBalanceForPeriod($account, $from, $to);

            if (
                Money::comp($periodDebits, '0') === 0
                && Money::comp($periodCredits, '0') === 0
                && Money::comp($closingSigned, '0') === 0
            ) {
                continue;
            }

            [$balanceDebit, $balanceCredit] = $this->presentBalance($account->type, $closingSigned);

            $totalBalanceDebit = Money::add($totalBalanceDebit, $balanceDebit);
            $totalBalanceCredit = Money::add($totalBalanceCredit, $balanceCredit);
            $totalActivityDebit = Money::add($totalActivityDebit, $periodDebits);
            $totalActivityCredit = Money::add($totalActivityCredit, $periodCredits);

            $rows[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'period_debits' => (float) $periodDebits,
                'period_credits' => (float) $periodCredits,
                'balance_debit' => (float) $balanceDebit,
                'balance_credit' => (float) $balanceCredit,
            ];
        }

        $variance = Money::sub($totalBalanceDebit, $totalBalanceCredit);
        $balanced = Money::comp($variance, '0') === 0;

        $fiscalYear = (int) $to->format('Y');
        $fiscalMonth = (int) $to->format('n');
        $periodClose = $this->periodCloseRecord($companyId, $fiscalYear, $fiscalMonth);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'fiscal_year' => $fiscalYear,
                'fiscal_month' => $fiscalMonth,
            ],
            'totals' => [
                'balance_debit' => (float) $totalBalanceDebit,
                'balance_credit' => (float) $totalBalanceCredit,
                'activity_debit' => (float) $totalActivityDebit,
                'activity_credit' => (float) $totalActivityCredit,
                'balanced' => $balanced,
                'variance' => (float) $variance,
            ],
            'period_close' => $periodClose ? [
                'is_closed' => true,
                'closed_at' => $periodClose->closed_at?->toIso8601String(),
                'closed_by' => $periodClose->closed_by,
                'trial_balance_balanced' => $periodClose->trial_balance_balanced,
            ] : [
                'is_closed' => false,
            ],
            'controls' => [
                'can_close_period' => $balanced && $periodClose === null,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function closePeriod(User $user, int $fiscalYear, int $fiscalMonth, ?string $notes = null): array
    {
        if ($fiscalMonth < 1 || $fiscalMonth > 12) {
            throw new BusinessRuleException('Fiscal month must be between 1 and 12.', 'INVALID_FISCAL_MONTH');
        }

        $from = Carbon::create($fiscalYear, $fiscalMonth, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $report = $this->report($user, $from, $to);

        if (! $report['totals']['balanced']) {
            throw new BusinessRuleException(
                'Trial balance must balance before closing the period.',
                'TRIAL_BALANCE_UNBALANCED',
            );
        }

        if ($report['period_close']['is_closed']) {
            throw new BusinessRuleException(
                'This accounting period is already closed.',
                'PERIOD_ALREADY_CLOSED',
            );
        }

        DB::transaction(function () use ($user, $fiscalYear, $fiscalMonth, $report, $notes) {
            AccountingPeriodClose::query()->create([
                'id' => (string) Str::uuid(),
                'company_id' => $user->company_id,
                'fiscal_year' => $fiscalYear,
                'fiscal_month' => $fiscalMonth,
                'trial_balance_balanced' => true,
                'total_debits' => $report['totals']['balance_debit'],
                'total_credits' => $report['totals']['balance_credit'],
                'closed_by' => $user->id,
                'closed_at' => now(),
                'notes' => $notes,
            ]);
        });

        return [
            'period_close' => [
                'is_closed' => true,
                'fiscal_year' => $fiscalYear,
                'fiscal_month' => $fiscalMonth,
                'closed_at' => now()->toIso8601String(),
                'trial_balance_balanced' => true,
            ],
            'report' => $this->report($user, $from, $to),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return $this->report($user, $from, $to)['rows'];
    }

    /**
     * @return array<string, array{period_debits: string, period_credits: string}>
     */
    private function periodActivity(string $companyId, Carbon $from, Carbon $to): array
    {
        $rows = JournalEntryLine::query()
            ->selectRaw('journal_entry_lines.account_id as account_id')
            ->selectRaw('SUM(journal_entry_lines.debit_amount) as period_debits')
            ->selectRaw('SUM(journal_entry_lines.credit_amount) as period_credits')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('journal_entry_lines.account_id')
            ->get();

        $activity = [];

        foreach ($rows as $row) {
            $activity[$row->account_id] = [
                'period_debits' => Money::toScale((string) $row->period_debits),
                'period_credits' => Money::toScale((string) $row->period_credits),
            ];
        }

        return $activity;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function presentBalance(string $accountType, string $signedBalance): array
    {
        if (Money::comp($signedBalance, '0') === 0) {
            return [Money::zero(), Money::zero()];
        }

        $isDebitNormal = in_array($accountType, [Account::TYPE_ASSET, Account::TYPE_EXPENSE], true);
        $positive = Money::comp($signedBalance, '0') > 0;
        $amount = $this->absAmount($signedBalance);

        if (($isDebitNormal && $positive) || (! $isDebitNormal && ! $positive)) {
            return [$amount, Money::zero()];
        }

        return [Money::zero(), $amount];
    }

    private function absAmount(string $amount): string
    {
        return Money::comp($amount, '0') < 0
            ? Money::sub(Money::zero(), $amount)
            : $amount;
    }

    private function periodCloseRecord(string $companyId, int $year, int $month): ?AccountingPeriodClose
    {
        return AccountingPeriodClose::query()
            ->where('company_id', $companyId)
            ->where('fiscal_year', $year)
            ->where('fiscal_month', $month)
            ->first();
    }
}
