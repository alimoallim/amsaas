<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\RentalAgreement;
use App\Support\Money;
use Illuminate\Support\Collection;

class AgreementUtilityUsageService
{
    /**
     * Latest meter readings and utility charges for a rental agreement.
     *
     * @return array{
     *     items: list<array<string, mixed>>,
     *     totals: array<string, mixed>
     * }
     */
    public function summarize(RentalAgreement $rental): array
    {
        $agreement = $rental->agreement;
        if (! $agreement) {
            return $this->emptyPayload();
        }

        $apartmentId = $agreement->apartment_id;
        $companyId = $agreement->company_id;
        $agreementId = $agreement->id;

        if (! $companyId || ! $apartmentId) {
            return $this->emptyPayload();
        }

        $configuredUtilities = $this->configuredUtilityTypes($agreement);
        if ($configuredUtilities === []) {
            return $this->emptyPayload();
        }

        $meterIds = $this->resolveMeterIds($agreement);
        if ($meterIds === []) {
            return $this->emptyPayload();
        }

        $rateByUtility = $this->resolveUnitRatesByUtility($agreement);
        $readings = $this->latestReadingsByUtility($companyId, $meterIds, $configuredUtilities);

        $items = [];
        $utilityChargesTotal = 0.0;
        $utilityChargesPending = 0.0;

        foreach ($readings as $utilityType => $reading) {
            $meter = $reading->meter;
            $utilityLabel = $this->utilityLabel($utilityType);
            $unit = $meter?->measurement_unit ?? '';
            $consumption = (float) $reading->consumption;

            $charge = Charge::query()
                ->where('company_id', $companyId)
                ->where('meter_reading_id', $reading->id)
                ->where('rental_agreement_id', $agreementId)
                ->orderByDesc('charged_at')
                ->first();

            $unitRate = $charge
                ? (float) ($charge->unit_rate ?? 0)
                : ($rateByUtility[$utilityType] ?? null);

            if ($unitRate === null || $unitRate <= 0) {
                $unitRate = $this->fallbackUnitRate($companyId, $utilityType);
            }

            $amount = $charge
                ? (float) $charge->total_amount
                : ($unitRate !== null
                    ? (float) Money::mul(Money::toScale((string) $consumption, 4), Money::toScale((string) $unitRate, 4))
                    : null);

            if ($amount !== null) {
                $utilityChargesTotal += $amount;
                if ($charge && in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_DRAFT], true)) {
                    $utilityChargesPending += $amount;
                } elseif (! $charge && ! $reading->isApproved()) {
                    $utilityChargesPending += $amount;
                }
            }

            $items[] = [
                'utility_type' => $utilityType,
                'utility_label' => $utilityLabel,
                'meter_id' => $reading->meter_id,
                'meter_number' => $meter?->meter_number,
                'reading_id' => $reading->id,
                'reading_date' => $reading->reading_date?->toDateString(),
                'reading_status' => $reading->status,
                'reading_status_label' => $this->readingStatusLabel($reading->status),
                'previous_reading' => (float) $reading->previous_reading,
                'current_reading' => (float) $reading->current_reading,
                'consumption' => $consumption,
                'measurement_unit' => $unit,
                'unit_rate' => $unitRate,
                'amount' => $amount,
                'amount_is_estimated' => $charge === null && $unitRate !== null,
                'charge_id' => $charge?->id,
                'charge_number' => $charge?->charge_number,
                'charge_status' => $charge?->status,
                'currency' => $charge?->currency ?? $rateByUtility['_currency'] ?? 'USD',
            ];
        }

        usort($items, fn ($a, $b) => strcmp($a['utility_label'], $b['utility_label']));

        return [
            'items' => $items,
            'totals' => [
                'readings_count' => count($items),
                'utility_charges_total' => round($utilityChargesTotal, 2),
                'utility_charges_pending' => round($utilityChargesPending, 2),
                'consumption_by_utility' => collect($items)->mapWithKeys(
                    fn ($row) => [$row['utility_type'] => $row['consumption']]
                )->all(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function configuredUtilityTypes(Agreement $agreement): array
    {
        $charges = $agreement->relationLoaded('agreementCharges')
            ? $agreement->agreementCharges
            : AgreementCharge::query()
                ->where('agreement_id', $agreement->id)
                ->with('chargeModel')
                ->get();

        $types = [];

        foreach ($charges as $line) {
            $model = $line->chargeModel;
            if (! $model || $model->pricing_strategy !== ChargeModel::STRATEGY_METERED) {
                continue;
            }

            $utility = $model->meter_type ?? $this->inferUtilityType($model->name, $line->custom_name);
            if ($utility) {
                $types[] = $utility;
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * @return list<string>
     */
    protected function resolveMeterIds(Agreement $agreement): array
    {
        return Meter::query()
            ->assignedToAgreement($agreement)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<string, float|null>
     */
    protected function resolveUnitRatesByUtility(Agreement $agreement): array
    {
        $charges = $agreement->relationLoaded('agreementCharges')
            ? $agreement->agreementCharges
            : AgreementCharge::query()
                ->where('agreement_id', $agreement->id)
                ->with('chargeModel')
                ->get();

        $rates = ['_currency' => $agreement->currency ?? 'USD'];

        foreach ($charges as $line) {
            $model = $line->chargeModel;
            if (! $model || $model->pricing_strategy !== ChargeModel::STRATEGY_METERED) {
                continue;
            }

            $utility = $model->meter_type ?? $this->inferUtilityType($model->name, $line->custom_name);
            if (! $utility) {
                continue;
            }

            $rates[$utility] = $line->override_unit_rate !== null
                ? (float) $line->override_unit_rate
                : (float) ($model->unit_rate ?? 0);
        }

        return $rates;
    }

    protected function fallbackUnitRate(string $companyId, string $utilityType): ?float
    {
        $rate = ChargeModel::query()
            ->where('company_id', $companyId)
            ->where('status', ChargeModel::STATUS_ACTIVE)
            ->where('pricing_strategy', ChargeModel::STRATEGY_METERED)
            ->where('meter_type', $utilityType)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('unit_rate');

        return $rate !== null ? (float) $rate : null;
    }

    protected function inferUtilityType(?string $modelName, ?string $customName): ?string
    {
        $haystack = strtolower(trim(($customName ?? '').' '.($modelName ?? '')));

        if ($haystack === '') {
            return null;
        }

        $known = [
            'chilled_water',
            'electricity',
            'internet',
            'steam',
            'water',
            'gas',
            'solar',
        ];

        foreach ($known as $type) {
            if (str_contains($haystack, str_replace('_', ' ', $type)) || str_contains($haystack, $type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $meterIds
     * @param  list<string>  $utilityTypes
     * @return Collection<string, MeterReading>
     */
    protected function latestReadingsByUtility(
        string $companyId,
        array $meterIds,
        array $utilityTypes,
    ): Collection {
        if ($meterIds === []) {
            return collect();
        }

        return MeterReading::query()
            ->with('meter')
            ->where('company_id', $companyId)
            ->whereIn('meter_id', $meterIds)
            ->whereHas('meter', fn ($q) => $q->whereIn('utility_type', $utilityTypes))
            ->whereNot('status', MeterReading::STATUS_REJECTED)
            ->orderByDesc('reading_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (MeterReading $r) => $r->meter?->utility_type ?? 'unknown')
            ->map(fn (Collection $group) => $group->first())
            ->filter(fn ($r) => $r && ($r->meter?->utility_type ?? null));
    }

    protected function utilityLabel(string $utilityType): string
    {
        return match ($utilityType) {
            'water' => 'Water',
            'electricity' => 'Electricity',
            'gas' => 'Gas',
            'steam' => 'Steam',
            'internet' => 'Internet',
            'solar' => 'Solar',
            'chilled_water' => 'Chilled water',
            default => ucfirst(str_replace('_', ' ', $utilityType)),
        };
    }

    protected function readingStatusLabel(string $status): string
    {
        return match ($status) {
            MeterReading::STATUS_APPROVED => 'Approved',
            MeterReading::STATUS_VERIFIED => 'Verified',
            MeterReading::STATUS_DRAFT => 'Draft',
            default => ucfirst($status),
        };
    }

    /**
     * @return array{items: array, totals: array}
     */
    protected function emptyPayload(): array
    {
        return [
            'items' => [],
            'totals' => [
                'readings_count' => 0,
                'utility_charges_total' => 0,
                'utility_charges_pending' => 0,
                'consumption_by_utility' => [],
            ],
        ];
    }
}
