<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialAuditService
{
    /** @var array<string, class-string> */
    public const ENTITY_MAP = [
        'payment' => Payment::class,
        'monthly_invoice' => MonthlyInvoice::class,
        'journal_entry' => JournalEntry::class,
        'account' => Account::class,
    ];

    /**
     * @return array{rows: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function timeline(
        User $user,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?string $entityType = null,
        ?string $action = null,
        int $perPage = 25,
        int $page = 1,
    ): array {
        $from = ($from ?? now()->subDays(30))->copy()->startOfDay();
        $to = ($to ?? now())->copy()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $entityClass = $entityType ? (self::ENTITY_MAP[$entityType] ?? null) : null;
        $rows = collect();

        $auditQuery = AuditLog::query()
            ->with('user:id,name')
            ->where('company_id', $user->company_id)
            ->whereIn('entity_type', array_values(self::ENTITY_MAP))
            ->whereBetween('created_at', [$from, $to]);

        if ($entityClass) {
            $auditQuery->where('entity_type', $entityClass);
        }

        if ($action) {
            $auditQuery->where('action', $action);
        }

        foreach ($auditQuery->orderByDesc('created_at')->get() as $log) {
            $rows->push($this->formatAuditLogRow($log));
        }

        if (! $entityClass || $entityClass === JournalEntry::class) {
            $journalQuery = JournalEntry::query()
                ->with('createdBy:id,name')
                ->where('company_id', $user->company_id)
                ->where('status', JournalEntry::STATUS_POSTED)
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);

            $loggedJournalIds = $rows
                ->where('entity_type', 'journal_entry')
                ->pluck('entity_id')
                ->filter()
                ->all();

            if ($loggedJournalIds !== []) {
                $journalQuery->whereNotIn('id', $loggedJournalIds);
            }

            foreach ($journalQuery->orderByDesc('entry_date')->orderByDesc('entry_number')->get() as $entry) {
                if ($action && $action !== 'posted') {
                    continue;
                }

                $rows->push($this->formatJournalRow($entry));
            }
        }

        $sorted = $rows
            ->sortByDesc(fn (array $row) => $row['occurred_at'])
            ->values();

        $total = $sorted->count();
        $offset = max(0, ($page - 1) * $perPage);
        $pageRows = $sorted->slice($offset, $perPage)->values()->all();

        return [
            'rows' => $pageRows,
            'meta' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(
        User $user,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?string $entityType = null,
        ?string $action = null,
    ): array {
        $result = $this->timeline($user, $from, $to, $entityType, $action, perPage: 10000, page: 1);

        return $result['rows'];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAuditLogRow(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'source' => 'audit_log',
            'occurred_at' => $log->created_at?->toIso8601String(),
            'action' => $log->action,
            'entity_type' => $this->friendlyEntityType($log->entity_type),
            'entity_id' => $log->entity_id,
            'summary' => $this->summarizeAuditLog($log),
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
            ] : null,
            'changes' => [
                'old' => $log->old_values,
                'new' => $log->new_values,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatJournalRow(JournalEntry $entry): array
    {
        return [
            'id' => 'journal-'.$entry->id,
            'source' => 'journal_entry',
            'occurred_at' => $entry->entry_date?->startOfDay()->toIso8601String()
                ?? $entry->created_at?->toIso8601String(),
            'action' => 'posted',
            'entity_type' => 'journal_entry',
            'entity_id' => $entry->id,
            'summary' => $entry->description.' ('.$entry->entry_number.')',
            'user' => $entry->createdBy ? [
                'id' => $entry->createdBy->id,
                'name' => $entry->createdBy->name,
            ] : null,
            'changes' => [
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'total_debit' => (float) $entry->total_debit,
                'total_credit' => (float) $entry->total_credit,
            ],
        ];
    }

    private function friendlyEntityType(string $class): string
    {
        return array_search($class, self::ENTITY_MAP, true) ?: class_basename($class);
    }

    private function summarizeAuditLog(AuditLog $log): string
    {
        $label = str_replace('_', ' ', $this->friendlyEntityType($log->entity_type));

        return match ($log->action) {
            'created' => ucfirst($label).' created',
            'updated' => ucfirst($label).' updated',
            'deleted' => ucfirst($label).' deleted',
            default => ucfirst($label).' '.$log->action,
        };
    }
}
