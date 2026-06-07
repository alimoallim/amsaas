<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MonthlyInvoiceListService
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));

        return $this->baseQuery($filters)
            ->with(['apartment.building'])
            ->orderByDesc('issue_date')
            ->orderBy('invoice_number')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(int $year, int $month): array
    {
        $companyId = $this->user->company_id;

        $rows = MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount')
            ->groupBy('status')
            ->get();

        $byStatus = $rows->pluck('count', 'status')->map(fn ($c) => (int) $c)->all();
        $amountByStatus = $rows->pluck('amount', 'status')->map(fn ($a) => round((float) $a, 2))->all();

        $draft = (int) ($byStatus['draft'] ?? 0);
        $issued = (int) (($byStatus['issued'] ?? 0) + ($byStatus['finalized'] ?? 0));
        $partiallyPaid = (int) ($byStatus['partially_paid'] ?? 0);
        $paid = (int) ($byStatus['paid'] ?? 0);
        $cancelled = (int) ($byStatus['cancelled'] ?? 0);
        $overdue = (int) ($byStatus['overdue'] ?? 0);

        $openBalance = (float) MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid', 'overdue'])
            ->whereRaw('balance_due > 0')
            ->sum('balance_due');

        $draftBalance = (float) MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->where('status', 'draft')
            ->whereRaw('balance_due > 0')
            ->sum('balance_due');

        return [
            'period' => sprintf('%04d-%02d', $year, $month),
            'counts' => [
                'draft' => $draft,
                'issued' => $issued,
                'partially_paid' => $partiallyPaid,
                'paid' => $paid,
                'cancelled' => $cancelled,
                'overdue' => $overdue,
                'total' => $draft + $issued + $partiallyPaid + $paid + $cancelled + $overdue,
            ],
            'amounts' => [
                'draft' => $amountByStatus['draft'] ?? 0,
                'draft_balance' => round($draftBalance, 2),
                'billed' => round(array_sum($amountByStatus), 2),
                'open_balance' => round($openBalance, 2),
            ],
            'needs_attention' => $draft,
            'can_bulk_issue' => $draft > 0,
        ];
    }

    /**
     * @param  list<string>|null  $ids
     * @return array{issued: int, failed: int, skipped: int}
     */
    public function bulkIssue(int $year, int $month, ?array $ids = null): array
    {
        $query = MonthlyInvoice::query()
            ->where('company_id', $this->user->company_id)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->where('status', 'draft')
            ->orderBy('issue_date')
            ->orderBy('created_at');

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => ['No draft invoices found for this period.'],
            ]);
        }

        $pipeline = app(BillingPipelineService::class, ['user' => $this->user]);
        $issued = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->status !== 'draft') {
                $skipped++;

                continue;
            }

            try {
                $pipeline->issueInvoice($invoice);
                $invoice->refresh();

                if ($invoice->status === 'issued') {
                    $invoice->update(['finalized_by' => $this->user->id]);
                    $issued++;
                } else {
                    $failed++;
                }
            } catch (\Throwable) {
                $failed++;
            }
        }

        return [
            'issued' => $issued,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  iterable<MonthlyInvoice>  $invoices
     */
    public function agreementsForInvoices(iterable $invoices): Collection
    {
        $agreementIds = collect($invoices)
            ->filter(fn (MonthlyInvoice $inv) => $inv->contract_type === 'rental')
            ->pluck('contract_id')
            ->unique()
            ->values();

        if ($agreementIds->isEmpty()) {
            return collect();
        }

        return Agreement::query()
            ->where('company_id', $this->user->company_id)
            ->whereIn('id', $agreementIds)
            ->with([
                'tenant:id,display_name,tenant_code,first_name,middle_name,last_name',
                'apartment:id,unit_number,building_id',
            ])
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function baseQuery(array $filters): Builder
    {
        $companyId = $this->user->company_id;

        $query = MonthlyInvoice::query()->where('company_id', $companyId);

        if (! empty($filters['year'])) {
            $query->where('billing_year', (int) $filters['year']);
        }

        if (! empty($filters['month'])) {
            $query->where('billing_month', (int) $filters['month']);
        }

        $view = $filters['view'] ?? 'attention';

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } elseif ($view === 'attention') {
            $query->where('status', 'draft');
        }

        if (! empty($filters['building_id'])) {
            $query->whereHas('apartment', fn (Builder $q) => $q->where('building_id', $filters['building_id']));
        }

        if (! empty($filters['search'])) {
            $term = '%'.addcslashes((string) $filters['search'], '%_\\').'%';
            $query->where(function (Builder $q) use ($term, $companyId) {
                $q->where('invoice_number', 'ilike', $term)
                    ->orWhereHas('apartment', fn (Builder $aq) => $aq
                        ->where('unit_number', 'ilike', $term)
                        ->orWhereHas('building', fn (Builder $bq) => $bq->where('name', 'ilike', $term)))
                    ->orWhere(function (Builder $inner) use ($term, $companyId) {
                        $inner->where('contract_type', 'rental')
                            ->whereIn('contract_id', Agreement::query()
                                ->where('company_id', $companyId)
                                ->whereHas('tenant', fn (Builder $tq) => $tq
                                    ->where('display_name', 'ilike', $term)
                                    ->orWhere('first_name', 'ilike', $term)
                                    ->orWhere('last_name', 'ilike', $term)
                                    ->orWhere('tenant_code', 'ilike', $term))
                                ->select('id'));
                    });
            });
        }

        return $query;
    }
}
