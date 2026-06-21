<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\BillingCloseReadinessService;
use App\Services\Billing\BillingPipelineService;
use App\Services\Billing\MonthlyInvoiceListService;
use App\Services\Collections\AgingReceivablesService;
use App\Services\Collections\DelinquencyTrackingService;
use Carbon\Carbon;

class DashboardSummaryService
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $companyId = $this->user->company_id;
        $year = $asOf->year;
        $month = $asOf->month;
        $billingDate = $asOf->copy()->startOfMonth();

        $portfolio = $this->portfolioMetrics($companyId, $asOf);
        $financials = $this->financialMetrics($year, $month);
        $agingReport = app(AgingReceivablesService::class)->report($this->user, $asOf, null, 'invoice');
        $delinquency = app(DelinquencyTrackingService::class)->listForCompany($this->user, null, null, $asOf);
        $pipeline = app(BillingPipelineService::class, ['user' => $this->user])->status($billingDate);
        $billingReadiness = app(BillingCloseReadinessService::class, ['user' => $this->user])
            ->metricsForPeriod($billingDate);
        $invoiceSummary = app(MonthlyInvoiceListService::class, ['user' => $this->user])
            ->summary($year, $month);

        $financials['outstanding_receivables'] = round(
            (float) ($agingReport['buckets']['total']['amount'] ?? 0),
            2
        );

        return [
            'user' => [
                'name' => $this->user->name,
                'company' => $this->user->company?->name,
            ],
            'period' => [
                'year' => $year,
                'month' => $month,
                'display' => $billingDate->format('F Y'),
            ],
            'portfolio' => $portfolio,
            'financials' => $financials,
            'collections' => [
                'aging_buckets' => $agingReport['buckets'],
                'delinquency' => [
                    'total' => $delinquency['total'],
                    'counts_by_stage' => $delinquency['counts_by_stage'],
                ],
            ],
            'operations' => [
                'pipeline' => $pipeline,
                'pending_utility_charges' => (int) ($pipeline['stages']['utility_charges']['pending_approval'] ?? 0),
                'approved_utility_charges' => (int) ($pipeline['stages']['utility_charges']['ready_to_invoice'] ?? 0),
                'draft_invoices' => (int) ($pipeline['stages']['invoices']['draft'] ?? 0),
                'issued_unpaid' => (int) ($pipeline['stages']['invoices']['issued_unpaid'] ?? 0),
                'meter_readings_pending' => (int) ($pipeline['stages']['meter_readings']['pending_approval'] ?? 0),
                'can_compile' => (bool) ($billingReadiness['can_compile'] ?? false),
                'agreements_missing_rent_charge' => (int) ($billingReadiness['agreements_missing_rent_charge'] ?? 0),
            ],
            'alerts' => $this->buildAlerts(
                $portfolio,
                $financials,
                $delinquency,
                $pipeline,
                $billingReadiness,
                $invoiceSummary,
            ),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    protected function portfolioMetrics(string $companyId, Carbon $asOf): array
    {
        $buildings = Building::query()
            ->where('company_id', $companyId)
            ->count();

        $apartmentQuery = Apartment::query()->where('company_id', $companyId);
        $apartments = (clone $apartmentQuery)->count();
        $occupied = (clone $apartmentQuery)
            ->where('inventory_status', Apartment::STATUS_OCCUPIED)
            ->count();
        $available = (clone $apartmentQuery)
            ->where('inventory_status', Apartment::STATUS_AVAILABLE)
            ->count();

        $occupancyRate = $apartments > 0
            ? round(($occupied / $apartments) * 100, 1)
            : 0.0;

        $tenants = Tenant::query()
            ->where('company_id', $companyId)
            ->count();

        $leases = Agreement::query()
            ->where('company_id', $companyId)
            ->where('agreement_type', Agreement::TYPE_RENTAL)
            ->where('status', Agreement::STATUS_ACTIVE)
            ->count();

        $expiringLeases30d = Agreement::query()
            ->where('company_id', $companyId)
            ->where('agreement_type', Agreement::TYPE_RENTAL)
            ->where('status', Agreement::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $asOf->toDateString())
            ->whereDate('end_date', '<=', $asOf->copy()->addDays(30)->toDateString())
            ->count();

        return [
            'buildings' => $buildings,
            'buildings_count' => $buildings,
            'apartments' => $apartments,
            'occupied' => $occupied,
            'vacant' => $available,
            'available' => $available,
            'occupancy_rate' => $occupancyRate,
            'tenants' => $tenants,
            'leases' => $leases,
            'expiring_leases_30d' => $expiringLeases30d,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    protected function financialMetrics(int $year, int $month): array
    {
        $companyId = $this->user->company_id;
        $invoiceSummary = app(MonthlyInvoiceListService::class, ['user' => $this->user])
            ->summary($year, $month);

        $invoiceBase = MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->whereNotIn('status', ['draft', 'cancelled']);

        $rentRevenue = (float) (clone $invoiceBase)->sum('subtotal_rent');
        $utilityRevenue = (float) (clone $invoiceBase)->sum('subtotal_utilities');
        $collected = (float) (clone $invoiceBase)->sum('paid_amount');
        $billed = (float) ($invoiceSummary['amounts']['billed'] ?? 0);
        $openBalance = (float) ($invoiceSummary['amounts']['open_balance'] ?? 0);

        $collectedMtd = (float) Payment::query()
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');

        $collectionRate = $billed > 0
            ? round(($collected / $billed) * 100, 1)
            : 0.0;

        return [
            'outstanding_receivables' => 0,
            'collected_mtd' => round($collectedMtd, 2),
            'rent_revenue' => round($rentRevenue, 2),
            'utility_revenue' => round($utilityRevenue, 2),
            'collected' => round($collected, 2),
            'outstanding' => round($openBalance, 2),
            'billed_mtd' => round($billed, 2),
            'collection_rate' => $collectionRate,
        ];
    }

    /**
     * @param  array<string, mixed>  $portfolio
     * @param  array<string, mixed>  $financials
     * @param  array<string, mixed>  $delinquency
     * @param  array<string, mixed>  $pipeline
     * @param  array<string, mixed>  $billingReadiness
     * @param  array<string, mixed>  $invoiceSummary
     * @return list<array<string, string>>
     */
    protected function buildAlerts(
        array $portfolio,
        array $financials,
        array $delinquency,
        array $pipeline,
        array $billingReadiness,
        array $invoiceSummary,
    ): array {
        $alerts = [];

        $delinquentTotal = (int) ($delinquency['total'] ?? 0);
        if ($delinquentTotal > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Delinquent accounts',
                'message' => "{$delinquentTotal} overdue invoice(s) need collections follow-up.",
                'href' => '/reports',
            ];
        }

        $draftInvoices = (int) ($pipeline['stages']['invoices']['draft'] ?? 0);
        if ($draftInvoices > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Draft invoices',
                'message' => "{$draftInvoices} draft invoice(s) are ready for review and issue.",
                'href' => '/invoices/monthly',
            ];
        }

        $pendingUtilities = (int) ($pipeline['stages']['utility_charges']['pending_approval'] ?? 0);
        if ($pendingUtilities > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Utility charges pending',
                'message' => "{$pendingUtilities} utility charge(s) await approval before billing close.",
                'href' => '/charges',
            ];
        }

        $pendingReadings = (int) ($pipeline['stages']['meter_readings']['pending_approval'] ?? 0);
        if ($pendingReadings > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Meter readings',
                'message' => "{$pendingReadings} meter reading(s) still need approval.",
                'href' => '/meter-readings',
            ];
        }

        $missingRent = (int) ($billingReadiness['agreements_missing_rent_charge'] ?? 0);
        if ($missingRent > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Lease billing setup',
                'message' => "{$missingRent} active lease(s) are missing a rent charge model.",
                'href' => '/rental-agreements',
            ];
        }

        $expiring = (int) ($portfolio['expiring_leases_30d'] ?? 0);
        if ($expiring > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Lease renewals',
                'message' => "{$expiring} active lease(s) expire within 30 days.",
                'href' => '/rental-agreements',
            ];
        }

        $outstanding = (float) ($financials['outstanding_receivables'] ?? 0);
        if ($outstanding > 0 && $delinquentTotal === 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Open receivables',
                'message' => 'Outstanding balances are current — monitor collections before due dates.',
                'href' => '/reports',
            ];
        }

        if ((int) ($invoiceSummary['needs_attention'] ?? 0) > 0 && $draftInvoices === 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Billing period',
                'message' => 'This period has invoice work items that need attention.',
                'href' => '/invoices/monthly',
            ];
        }

        return $alerts;
    }
}
