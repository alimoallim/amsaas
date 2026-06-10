<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IncomeStatementService
{
    /**
     * @return array<string, mixed>
     */
    public function report(
        User $user,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?int $billingYear = null,
        ?int $billingMonth = null,
    ): array {
        if ($billingYear !== null && $billingMonth !== null) {
            $from = Carbon::create($billingYear, $billingMonth, 1)->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } else {
            $from = ($from ?? now()->startOfMonth())->copy()->startOfDay();
            $to = ($to ?? now()->endOfMonth())->copy()->endOfDay();
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $companyId = $user->company_id;
        $company = Company::query()->find($companyId);
        $activity = $this->periodActivityByAccount($companyId, $from, $to, $billingYear, $billingMonth);

        $revenueRows = [];
        $expenseRows = [];
        $totalRevenue = Money::zero();
        $totalExpenses = Money::zero();

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('status', Account::STATUS_ACTIVE)
            ->whereIn('type', [Account::TYPE_REVENUE, Account::TYPE_EXPENSE])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $stats = $activity[$account->id] ?? [
                'period_debits' => Money::zero(),
                'period_credits' => Money::zero(),
            ];

            $amount = $this->incomeStatementAmount(
                $account->type,
                $stats['period_debits'],
                $stats['period_credits'],
            );

            if (Money::comp($amount, '0') === 0) {
                continue;
            }

            $row = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'amount' => (float) $amount,
            ];

            if ($account->type === Account::TYPE_REVENUE) {
                $revenueRows[] = $row;
                $totalRevenue = Money::add($totalRevenue, $amount);
            } else {
                $expenseRows[] = $row;
                $totalExpenses = Money::add($totalExpenses, $amount);
            }
        }

        $netIncome = Money::sub($totalRevenue, $totalExpenses);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'billing_year' => $billingYear,
                'billing_month' => $billingMonth,
            ],
            'company' => [
                'id' => $company?->id,
                'name' => $company?->name,
                'currency_code' => $company?->currency_code ?? 'USD',
            ],
            'sections' => [
                'revenue' => [
                    'rows' => $revenueRows,
                    'total' => (float) $totalRevenue,
                ],
                'expenses' => [
                    'rows' => $expenseRows,
                    'total' => (float) $totalExpenses,
                ],
            ],
            'totals' => [
                'gross_revenue' => (float) $totalRevenue,
                'total_expenses' => (float) $totalExpenses,
                'net_income' => (float) $netIncome,
            ],
        ];
    }

    public function pdfBinary(
        User $user,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?int $billingYear = null,
        ?int $billingMonth = null,
    ): ?string {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::info('DomPDF not installed; skipping income statement PDF.');

            return null;
        }

        $report = $this->report($user, $from, $to, $billingYear, $billingMonth);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.income_statement', [
            'report' => $report,
        ]);

        return $pdf->output();
    }

    /**
     * @return array<string, array{period_debits: string, period_credits: string}>
     */
    private function periodActivityByAccount(
        string $companyId,
        Carbon $from,
        Carbon $to,
        ?int $billingYear = null,
        ?int $billingMonth = null,
    ): array {
        $rows = JournalEntryLine::query()
            ->selectRaw('journal_entry_lines.account_id as account_id')
            ->selectRaw('SUM(journal_entry_lines.debit_amount) as period_debits')
            ->selectRaw('SUM(journal_entry_lines.credit_amount) as period_credits')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->where(function ($query) use ($from, $to, $billingYear, $billingMonth) {
                if ($billingYear !== null && $billingMonth !== null) {
                    $query->where(function ($inner) use ($billingYear, $billingMonth) {
                        $inner->where('journal_entries.fiscal_year', $billingYear)
                            ->where('journal_entries.fiscal_month', $billingMonth);
                    });
                } else {
                    $query->whereBetween('journal_entries.entry_date', [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]);
                }
            })
            ->whereIn('accounts.type', [Account::TYPE_REVENUE, Account::TYPE_EXPENSE])
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

    private function incomeStatementAmount(string $accountType, string $debits, string $credits): string
    {
        if ($accountType === Account::TYPE_REVENUE) {
            return Money::sub($credits, $debits);
        }

        return Money::sub($debits, $credits);
    }
}
