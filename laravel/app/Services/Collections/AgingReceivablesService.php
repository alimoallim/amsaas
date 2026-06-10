<?php

namespace App\Services\Collections;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AgingReceivablesService
{
    public const BUCKET_CURRENT = 'current';

    public const BUCKET_DAYS_1_30 = 'days_1_30';

    public const BUCKET_DAYS_31_60 = 'days_31_60';

    public const BUCKET_DAYS_61_90 = 'days_61_90';

    public const BUCKET_DAYS_OVER_90 = 'days_over_90';

    /**
     * @return array<string, mixed>
     */
    public function report(
        User $user,
        ?Carbon $asOf = null,
        ?string $buildingId = null,
        string $groupBy = 'tenant',
    ): array {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $groupBy = in_array($groupBy, ['tenant', 'building', 'invoice'], true) ? $groupBy : 'tenant';

        $invoices = $this->openInvoices($user, $buildingId);
        $agreements = $this->agreementsForInvoices($invoices);

        $bucketTotals = $this->emptyBuckets();
        $rows = [];

        foreach ($invoices as $invoice) {
            $balance = round((float) $invoice->balance_due, 2);
            if ($balance <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->startOfDay() : null;
            $bucket = $this->bucketForDueDate($dueDate, $asOf);
            $daysOverdue = $this->daysOverdue($dueDate, $asOf);

            $bucketTotals[$bucket]['count']++;
            $bucketTotals[$bucket]['amount'] = round($bucketTotals[$bucket]['amount'] + $balance, 2);
            $bucketTotals['total']['count']++;
            $bucketTotals['total']['amount'] = round($bucketTotals['total']['amount'] + $balance, 2);

            $agreement = $agreements->get($invoice->contract_id);
            $tenant = $agreement?->tenant;
            $building = $invoice->apartment?->building;

            if ($groupBy === 'invoice') {
                $rows[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'due_date' => $dueDate?->toDateString(),
                    'days_overdue' => $daysOverdue,
                    'bucket' => $bucket,
                    'balance_due' => $balance,
                    'status' => $invoice->status,
                    'tenant' => $tenant ? [
                        'id' => $tenant->id,
                        'display_name' => $tenant->full_display_name ?: null,
                        'tenant_code' => $tenant->tenant_code,
                    ] : null,
                    'building' => $building ? [
                        'id' => $building->id,
                        'name' => $building->name,
                    ] : null,
                ];
            }
        }

        if ($groupBy !== 'invoice') {
            $rows = $this->aggregateGroupedRows($invoices, $agreements, $asOf, $groupBy);
        }

        usort($rows, fn (array $a, array $b) => ($b['total_balance'] ?? $b['balance_due'] ?? 0)
            <=> ($a['total_balance'] ?? $a['balance_due'] ?? 0));

        return [
            'as_of' => $asOf->toDateString(),
            'group_by' => $groupBy,
            'building_id' => $buildingId,
            'buckets' => $bucketTotals,
            'rows' => array_values($rows),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function exportRows(
        User $user,
        ?Carbon $asOf = null,
        ?string $buildingId = null,
    ): array {
        $report = $this->report($user, $asOf, $buildingId, 'invoice');

        return $report['rows'];
    }

    /**
     * @return Collection<int, MonthlyInvoice>
     */
    protected function openInvoices(User $user, ?string $buildingId): Collection
    {
        $query = MonthlyInvoice::query()
            ->where('company_id', $user->company_id)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid', 'overdue'])
            ->whereRaw('balance_due > 0')
            ->with(['apartment.building'])
            ->orderBy('due_date');

        if ($buildingId) {
            $query->whereHas('apartment', fn ($q) => $q->where('building_id', $buildingId));
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, MonthlyInvoice>  $invoices
     * @return Collection<string, Agreement>
     */
    protected function agreementsForInvoices(Collection $invoices): Collection
    {
        $agreementIds = $invoices
            ->where('contract_type', 'rental')
            ->pluck('contract_id')
            ->filter()
            ->unique()
            ->values();

        if ($agreementIds->isEmpty()) {
            return collect();
        }

        return Agreement::query()
            ->whereIn('id', $agreementIds)
            ->with('tenant')
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, MonthlyInvoice>  $invoices
     * @param  Collection<string, Agreement>  $agreements
     * @return list<array<string, mixed>>
     */
    protected function aggregateGroupedRows(
        Collection $invoices,
        Collection $agreements,
        Carbon $asOf,
        string $groupBy,
    ): array {
        $grouped = [];

        foreach ($invoices as $invoice) {
            $balance = round((float) $invoice->balance_due, 2);
            if ($balance <= 0) {
                continue;
            }

            $agreement = $agreements->get($invoice->contract_id);
            $tenant = $agreement?->tenant;
            $building = $invoice->apartment?->building;

            $groupKey = $groupBy === 'building'
                ? ($building?->id ?? 'unknown')
                : ($tenant?->id ?? 'unknown');

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'tenant_id' => $tenant?->id,
                    'tenant_name' => $tenant?->full_display_name,
                    'tenant_code' => $tenant?->tenant_code,
                    'building_id' => $building?->id,
                    'building_name' => $building?->name,
                    'invoice_count' => 0,
                    'buckets' => $this->emptyBuckets(false),
                    'total_balance' => 0.0,
                ];
            }

            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->startOfDay() : null;
            $bucket = $this->bucketForDueDate($dueDate, $asOf);

            $grouped[$groupKey]['invoice_count']++;
            $grouped[$groupKey]['buckets'][$bucket]['count']++;
            $grouped[$groupKey]['buckets'][$bucket]['amount'] = round(
                $grouped[$groupKey]['buckets'][$bucket]['amount'] + $balance,
                2
            );
            $grouped[$groupKey]['total_balance'] = round(
                $grouped[$groupKey]['total_balance'] + $balance,
                2
            );
        }

        return array_values($grouped);
    }

    /**
     * @return array<string, array{count: int, amount: float}>
     */
    protected function emptyBuckets(bool $includeTotal = true): array
    {
        $buckets = [
            self::BUCKET_CURRENT => ['count' => 0, 'amount' => 0.0],
            self::BUCKET_DAYS_1_30 => ['count' => 0, 'amount' => 0.0],
            self::BUCKET_DAYS_31_60 => ['count' => 0, 'amount' => 0.0],
            self::BUCKET_DAYS_61_90 => ['count' => 0, 'amount' => 0.0],
            self::BUCKET_DAYS_OVER_90 => ['count' => 0, 'amount' => 0.0],
        ];

        if ($includeTotal) {
            $buckets['total'] = ['count' => 0, 'amount' => 0.0];
        }

        return $buckets;
    }

    public function bucketForDueDate(?Carbon $dueDate, Carbon $asOf): string
    {
        if (! $dueDate || $dueDate->gte($asOf)) {
            return self::BUCKET_CURRENT;
        }

        $days = $dueDate->diffInDays($asOf);

        if ($days <= 30) {
            return self::BUCKET_DAYS_1_30;
        }

        if ($days <= 60) {
            return self::BUCKET_DAYS_31_60;
        }

        if ($days <= 90) {
            return self::BUCKET_DAYS_61_90;
        }

        return self::BUCKET_DAYS_OVER_90;
    }

    public function daysOverdue(?Carbon $dueDate, Carbon $asOf): int
    {
        if (! $dueDate || $dueDate->gte($asOf)) {
            return 0;
        }

        return (int) $dueDate->diffInDays($asOf);
    }
}
