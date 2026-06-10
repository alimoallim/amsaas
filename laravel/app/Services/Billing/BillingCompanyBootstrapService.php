<?php

namespace App\Services\Billing;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\RentalAgreement;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * One-shot company billing setup: rent model, utility model fixes, charge sync, optional monthly close.
 */
class BillingCompanyBootstrapService
{
    /**
     * @return array<string, mixed>
     */
    public function bootstrap(
        Company $company,
        User $actor,
        ?Carbon $billingDate = null,
        bool $runMonthlyClose = false,
        bool $backfillUtilityCharges = true,
    ): array {
        TenantContext::setCompanyId((string) $company->id);

        $billingDate = ($billingDate ?? now())->copy()->startOfMonth();

        $result = [
            'company_id' => $company->id,
            'period' => $billingDate->format('Y-m'),
            'rent_charge_model' => null,
            'fixed_charge_models' => 0,
            'agreement_charges_synced' => 0,
            'utility_charges_backfilled' => 0,
            'monthly_close' => null,
        ];

        $rentModel = $this->ensureRentChargeModel($company, $actor);
        $result['rent_charge_model'] = $rentModel?->only(['id', 'code', 'name']);

        $result['fixed_charge_models'] = $this->fixUtilityChargeModelMeterTypes($company);

        app(BillingCloseReadinessService::class, ['user' => $actor])
            ->prepareActiveAgreementsForBilling();

        $rentals = RentalAgreement::query()
            ->whereHas('agreement', fn ($q) => $q
                ->where('company_id', $company->id)
                ->where('status', Agreement::STATUS_ACTIVE))
            ->with('agreement')
            ->get();

        $result['agreement_charges_synced'] = $rentals->count();

        if ($backfillUtilityCharges) {
            $result['utility_charges_backfilled'] = $this->backfillUtilityCharges($company, $actor);
        }

        if ($runMonthlyClose) {
            $close = app(BillingPipelineService::class, ['user' => $actor])
                ->runMonthlyClose($billingDate, true);
            $result['monthly_close'] = $close['consolidation'] ?? $close;
        }

        return $result;
    }

    protected function ensureRentChargeModel(Company $company, User $actor): ?ChargeModel
    {
        $existing = ChargeModel::query()
            ->where('company_id', $company->id)
            ->where('status', ChargeModel::STATUS_ACTIVE)
            ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT)
            ->first();

        if ($existing) {
            return $existing;
        }

        $chargeType = ChargeType::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();

        if (! $chargeType) {
            $chargeType = ChargeType::query()->create([
                'id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'code' => 'RENT',
                'name' => 'Monthly Rent',
                'category' => ChargeType::CATEGORY_RENT,
                'billing_behavior' => ChargeType::BILLING_FIXED,
                'calculation_method' => ChargeType::CALCULATION_FIXED,
                'billing_frequency' => ChargeType::FREQUENCY_MONTHLY,
                'financial_classification' => ChargeType::CLASSIFICATION_INCOME,
                'ledger_account_code' => Account::CODE_RENTAL_INCOME,
                'default_currency' => $company->currency_code ?? 'USD',
                'is_recurring' => true,
                'is_metered' => false,
                'requires_meter_reading' => false,
                'status' => ChargeType::STATUS_ACTIVE,
                'is_taxable' => false,
                'is_refundable' => false,
                'auto_generate' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }

        return ChargeModel::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'code' => 'RENT-MONTHLY',
            'name' => 'Monthly Rent Fee',
            'currency' => $company->currency_code ?? 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'billing_frequency' => 'monthly',
            'status' => ChargeModel::STATUS_ACTIVE,
            'effective_from' => now()->subYear()->toDateString(),
            'auto_generate' => false,
            'taxable' => false,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * Correct charge models whose name/code implies water but meter_type is wrong.
     */
    protected function fixUtilityChargeModelMeterTypes(Company $company): int
    {
        $fixed = 0;

        $models = ChargeModel::query()
            ->where('company_id', $company->id)
            ->where('pricing_strategy', ChargeModel::STRATEGY_METERED)
            ->where('auto_generate', true)
            ->get();

        foreach ($models as $model) {
            $label = strtoupper($model->name.' '.$model->code);
            $targetType = null;

            if (str_contains($label, 'WATER') && $model->meter_type !== Meter::UTILITY_WATER) {
                $targetType = Meter::UTILITY_WATER;
            } elseif (str_contains($label, 'ELECT') && $model->meter_type !== Meter::UTILITY_ELECTRICITY) {
                $targetType = Meter::UTILITY_ELECTRICITY;
            } elseif (str_contains($label, 'GAS') && $model->meter_type !== Meter::UTILITY_GAS) {
                $targetType = Meter::UTILITY_GAS;
            }

            if ($targetType) {
                $model->update(['meter_type' => $targetType]);
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Generate utility charges for approved readings that have none yet.
     */
    protected function backfillUtilityCharges(Company $company, User $actor): int
    {
        $readings = MeterReading::query()
            ->where('company_id', $company->id)
            ->where('status', MeterReading::STATUS_APPROVED)
            ->whereDoesntHave('utilityCharges')
            ->with('meter')
            ->get();

        $created = 0;
        $generator = app(GenerateChargeService::class);

        foreach ($readings as $reading) {
            try {
                $charges = $generator->generateFromMeterReading($reading);
                $created += $charges->count();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $created;
    }
}
