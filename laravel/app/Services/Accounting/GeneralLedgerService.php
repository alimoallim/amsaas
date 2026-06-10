<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GeneralLedgerService
{
    /**
     * @return array<string, mixed>
     */
    public function ledger(
        User $user,
        Account $account,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): array {
        $from = ($from ?? now()->startOfMonth())->copy()->startOfDay();
        $to = ($to ?? now())->copy()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $openingBalance = $this->balanceBefore($account, $from);
        $lines = $this->periodLines($user->company_id, $account->id, $from, $to);

        $running = $openingBalance;
        $periodDebits = Money::zero();
        $periodCredits = Money::zero();

        $rows = $lines->map(function (JournalEntryLine $line) use ($account, &$running, &$periodDebits, &$periodCredits) {
            $debit = Money::toScale((string) $line->debit_amount);
            $credit = Money::toScale((string) $line->credit_amount);
            $periodDebits = Money::add($periodDebits, $debit);
            $periodCredits = Money::add($periodCredits, $credit);

            $delta = $this->signedDelta($account->type, $debit, $credit);
            $running = Money::add($running, $delta);

            $entry = $line->journalEntry;

            return [
                'id' => $line->id,
                'entry_date' => $entry?->entry_date?->toDateString(),
                'entry_number' => $entry?->entry_number,
                'journal_entry_id' => $line->journal_entry_id,
                'description' => $line->description ?: $entry?->description,
                'source_type' => $entry?->source_type,
                'source_id' => $entry?->source_id,
                'debit_amount' => (float) $debit,
                'credit_amount' => (float) $credit,
                'running_balance' => (float) $running,
            ];
        })->values()->all();

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'opening_balance' => (float) $openingBalance,
                'period_debits' => (float) $periodDebits,
                'period_credits' => (float) $periodCredits,
                'closing_balance' => (float) $running,
                'transaction_count' => count($rows),
            ],
            'rows' => $rows,
        ];
    }

    public function balanceAsOf(Account $account, Carbon $asOf): string
    {
        return $this->balanceBefore($account, $asOf->copy()->addDay()->startOfDay());
    }

    public function closingBalanceForPeriod(Account $account, Carbon $from, Carbon $to): string
    {
        $opening = $this->balanceBefore($account, $from);
        $lines = $this->periodLines($account->company_id, $account->id, $from, $to);
        $balance = $opening;

        foreach ($lines as $line) {
            $balance = Money::add(
                $balance,
                $this->signedDelta(
                    $account->type,
                    Money::toScale((string) $line->debit_amount),
                    Money::toScale((string) $line->credit_amount),
                ),
            );
        }

        return $balance;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(
        User $user,
        Account $account,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): array {
        $ledger = $this->ledger($user, $account, $from, $to);
        $export = [];

        $export[] = [
            'entry_date' => $ledger['period']['from'],
            'entry_number' => '',
            'description' => 'Opening balance',
            'debit_amount' => '',
            'credit_amount' => '',
            'running_balance' => $ledger['summary']['opening_balance'],
        ];

        foreach ($ledger['rows'] as $row) {
            $export[] = $row;
        }

        return $export;
    }

    private function balanceBefore(Account $account, Carbon $beforeDate): string
    {
        $lines = JournalEntryLine::query()
            ->select('journal_entry_lines.*')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.company_id', $account->company_id)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->where('journal_entries.entry_date', '<', $beforeDate->toDateString())
            ->get();

        return $this->sumSignedDeltas($account->type, $lines);
    }

    /**
     * @return Collection<int, JournalEntryLine>
     */
    private function periodLines(string $companyId, string $accountId, Carbon $from, Carbon $to): Collection
    {
        return JournalEntryLine::query()
            ->select('journal_entry_lines.*')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()])
            ->with('journalEntry')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_number')
            ->orderBy('journal_entry_lines.line_order')
            ->get();
    }

    /**
     * @param  Collection<int, JournalEntryLine>  $lines
     */
    private function sumSignedDeltas(string $accountType, Collection $lines): string
    {
        $balance = Money::zero();

        foreach ($lines as $line) {
            $balance = Money::add(
                $balance,
                $this->signedDelta(
                    $accountType,
                    Money::toScale((string) $line->debit_amount),
                    Money::toScale((string) $line->credit_amount),
                ),
            );
        }

        return $balance;
    }

    private function signedDelta(string $accountType, string $debit, string $credit): string
    {
        if ($this->isDebitNormal($accountType)) {
            return Money::sub($debit, $credit);
        }

        return Money::sub($credit, $debit);
    }

    private function isDebitNormal(string $accountType): bool
    {
        return in_array($accountType, [Account::TYPE_ASSET, Account::TYPE_EXPENSE], true);
    }
}
