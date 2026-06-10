<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TenantBillingService
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Invoice history and balance summary for a tenant across all their agreements.
     *
     * @param  array<string, mixed>  $filters
     * @return array{summary: array<string, mixed>, paginator: LengthAwarePaginator}
     */
    public function billingHistory(Tenant $tenant, array $filters): array
    {
        $agreementIds = Agreement::query()
            ->where('company_id', $this->user->company_id)
            ->where('tenant_id', $tenant->id)
            ->pluck('id');

        $baseQuery = MonthlyInvoice::query()
            ->where('company_id', $this->user->company_id)
            ->where('contract_type', 'rental')
            ->whereIn('contract_id', $agreementIds);

        $summaryRows = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as invoiced, COALESCE(SUM(paid_amount), 0) as paid, COALESCE(SUM(balance_due), 0) as balance')
            ->groupBy('status')
            ->get();

        $openStatuses = ['issued', 'finalized', 'partially_paid', 'overdue'];
        $outstanding = (float) (clone $baseQuery)
            ->whereIn('status', $openStatuses)
            ->whereRaw('balance_due > 0')
            ->sum('balance_due');

        $summary = [
            'total_invoiced' => round((float) $summaryRows->sum('invoiced'), 2),
            'total_paid' => round((float) $summaryRows->sum('paid'), 2),
            'outstanding_balance' => round($outstanding, 2),
            'invoice_count' => (int) $summaryRows->sum('count'),
            'counts_by_status' => $summaryRows->pluck('count', 'status')->map(fn ($c) => (int) $c)->all(),
            'agreement_count' => $agreementIds->count(),
        ];

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));

        $paginator = $this->applyFilters($baseQuery, $filters)
            ->with(['apartment.building'])
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->orderByDesc('issue_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'summary' => $summary,
            'paginator' => $paginator,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['year'])) {
            $query->where('billing_year', (int) $filters['year']);
        }

        if (! empty($filters['month'])) {
            $query->where('billing_month', (int) $filters['month']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
