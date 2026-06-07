<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\BillingItem;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\RentalAgreement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class BillingCloseReadinessService
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function metricsForPeriod(Carbon $billingDate): array
    {
        $companyId = $this->user->company_id;
        $billingDate = $billingDate->copy()->startOfMonth();
        $periodStart = $billingDate->copy()->startOfMonth()->toDateString();
        $periodEnd = $billingDate->copy()->endOfMonth()->toDateString();

        $activeRentals = RentalAgreement::query()
            ->whereHas('agreement', fn (Builder $q) => $q
                ->where('company_id', $companyId)
                ->where('status', Agreement::STATUS_ACTIVE))
            ->count();

        $billableCharges = AgreementCharge::query()
            ->where('company_id', $companyId)
            ->where('status', AgreementCharge::STATUS_ACTIVE)
            ->where('is_suspended', false)
            ->whereHas('chargeModel', fn (Builder $q) => $q->whereIn('pricing_strategy', [
                ChargeModel::STRATEGY_AGREEMENT_RENT,
                ChargeModel::STRATEGY_FLAT_FEE,
                ChargeModel::STRATEGY_FIXED,
            ]))
            ->whereDate('billing_start_date', '<=', $billingDate->toDateString())
            ->where(function (Builder $q) use ($billingDate) {
                $q->whereNull('billing_end_date')
                    ->orWhereDate('billing_end_date', '>=', $billingDate->toDateString());
            })
            ->count();

        $missingRentCharge = RentalAgreement::query()
            ->whereHas('agreement', fn (Builder $q) => $q
                ->where('company_id', $companyId)
                ->where('status', Agreement::STATUS_ACTIVE))
            ->where('monthly_rent', '>', 0)
            ->whereDoesntHave('agreement.agreementCharges', function (Builder $q) {
                $q->where('status', AgreementCharge::STATUS_ACTIVE)
                    ->whereHas('chargeModel', fn (Builder $cm) => $cm
                        ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT));
            })
            ->count();

        $hasRentChargeModel = ChargeModel::query()
            ->where('company_id', $companyId)
            ->where('status', ChargeModel::STATUS_ACTIVE)
            ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT)
            ->exists();

        $pendingItemsQuery = BillingItem::query()
            ->where('company_id', $companyId)
            ->where('posted_to_invoice', false)
            ->whereDate('billing_period_start', $periodStart)
            ->whereDate('billing_period_end', $periodEnd);

        $pendingItemsCount = (clone $pendingItemsQuery)->count();
        $pendingItemsSum = (clone $pendingItemsQuery)->sum('total_amount');

        $pendingUtilitiesCount = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY)
            ->where('status', Charge::STATUS_PENDING)
            ->whereNull('invoice_id')
            ->count();

        $approvedUtilitiesCount = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY)
            ->where('status', Charge::STATUS_APPROVED)
            ->whereNull('invoice_id')
            ->count();

        $approvedUtilitiesSum = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY)
            ->where('status', Charge::STATUS_APPROVED)
            ->whereNull('invoice_id')
            ->sum('total_amount');

        $canCompile = $activeRentals > 0 || $pendingItemsCount > 0 || $approvedUtilitiesCount > 0;

        return [
            'fixed_items_count' => $pendingItemsCount,
            'fixed_items_revenue' => round((float) $pendingItemsSum, 2),
            'utility_items_pending_approval' => $pendingUtilitiesCount,
            'utility_items_ready' => $approvedUtilitiesCount,
            'utility_items_revenue' => round((float) $approvedUtilitiesSum, 2),
            'total_pending_rows' => $pendingItemsCount + $approvedUtilitiesCount,
            'estimated_total' => round((float) ($pendingItemsSum + $approvedUtilitiesSum), 2),
            'active_rental_agreements' => $activeRentals,
            'billable_agreement_charges' => $billableCharges,
            'agreements_missing_rent_charge' => $missingRentCharge,
            'has_rent_charge_model' => $hasRentChargeModel,
            'can_compile' => $canCompile,
        ];
    }

    /**
     * Ensure active leases have rent agreement charges before billing run.
     */
    public function prepareActiveAgreementsForBilling(): void
    {
        $companyId = $this->user->company_id;
        $sync = app(AgreementChargeSyncService::class);

        $rentals = RentalAgreement::query()
            ->whereHas('agreement', fn (Builder $q) => $q
                ->where('company_id', $companyId)
                ->where('status', Agreement::STATUS_ACTIVE))
            ->with('agreement')
            ->get();

        foreach ($rentals as $rental) {
            $agreement = $rental->agreement;
            if (! $agreement) {
                continue;
            }

            AgreementCharge::query()
                ->where('agreement_id', $agreement->id)
                ->where('status', AgreementCharge::STATUS_DRAFT)
                ->update(['status' => AgreementCharge::STATUS_ACTIVE]);

            $hasActiveRentCharge = AgreementCharge::query()
                ->where('agreement_id', $agreement->id)
                ->where('status', AgreementCharge::STATUS_ACTIVE)
                ->whereHas('chargeModel', fn (Builder $q) => $q
                    ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT))
                ->exists();

            if (! $hasActiveRentCharge && (float) $rental->monthly_rent > 0) {
                $sync->sync($this->user, $agreement, $rental, [], null);
            }
        }
    }
}
