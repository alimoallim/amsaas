<?php 
namespace App\Services\Billing;
use Carbon\Carbon;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\MeterReading;
use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Meter;
use App\Models\RentalAgreement;
use App\Services\Billing\CalculateChargeService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class GenerateChargeService
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Injects the standalone calculation strategy engine cleanly.
    |--------------------------------------------------------------------------
    */

    public function __construct(
        protected CalculateChargeService $calculator
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Charges From Approved Meter Reading
    |--------------------------------------------------------------------------
    | Binds the processing loop within a safe database transaction block.
    |--------------------------------------------------------------------------
    */

    public function generateFromMeterReading(MeterReading $reading): Collection
    {
        return DB::transaction(function () use ($reading) {
            $reading->loadMissing('meter');
            $meter = $reading->meter;

            if (! $meter) {
                return collect();
            }

            if (! $meter->canBillTenants()) {
                Log::info('Skipping tenant utility charge — meter is not assigned to a unit or tenant.', [
                    'meter_reading_id' => $reading->id,
                    'meter_id' => $meter->id,
                    'ownership_type' => $meter->ownership_type,
                ]);

                return collect();
            }

            $activeAgreement = $this->resolveActiveAgreement($reading, $meter);

            if (! $activeAgreement) {
                throw new BusinessRuleException(
                    'Cannot generate utility charges without an ACTIVE rental agreement for the meter assignment.',
                    'AGREEMENT_NOT_ACTIVE',
                );
            }

            if (! $meter->isAssignedToAgreement($activeAgreement->agreement)) {
                Log::info('Skipping tenant utility charge — meter is not assigned to this agreement.', [
                    'meter_reading_id' => $reading->id,
                    'meter_id' => $meter->id,
                    'agreement_id' => $activeAgreement->id,
                ]);

                return collect();
            }

            $chargeModels = $this->resolveChargeModels($reading, $activeAgreement);

            if ($chargeModels->isEmpty()) {
                Log::warning('No metered charge lines on this agreement for the meter utility type.', [
                    'meter_reading_id' => $reading->id,
                    'meter_type' => $meter->utility_type ?? 'UNKNOWN',
                    'agreement_id' => $activeAgreement->id,
                ]);

                return collect();
            }

            $generatedCharges = collect();

            // 4. Process and convert the reading metrics into structured tenant lines
            foreach ($chargeModels as $model) {
                $this->ensureChargeNotGeneratedForModel($reading, $model);
                $generatedCharges->push(
                    $this->generateCharge($reading, $model, $activeAgreement)
                );
            }

            Log::info('Utility billing lines generated successfully from approved reading.', [
                'meter_reading_id' => $reading->id,
                'charges_generated' => $generatedCharges->count(),
                'apartment_id' => $reading->apartment_id,
                'rental_agreement_id' => $activeAgreement?->id ?? 'VACANT-OWNER-PORTFOLIO'
            ]);

            return $generatedCharges;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Applicable Charge Models
    |--------------------------------------------------------------------------
    | Restricts profiles to matching companies, status flags, and utility types.
    |--------------------------------------------------------------------------
    */

    protected function resolveActiveAgreement(MeterReading $reading, Meter $meter): ?RentalAgreement
    {
        return RentalAgreement::query()
            ->with('agreement')
            ->whereHas('agreement', function ($query) use ($reading, $meter) {
                $query->where('company_id', $reading->company_id)
                    ->where('status', Agreement::STATUS_ACTIVE)
                    ->whereDate('start_date', '<=', $reading->reading_date)
                    ->where(function ($inner) use ($reading) {
                        $inner->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $reading->reading_date);
                    });

                if ($meter->apartment_id) {
                    $query->where('apartment_id', $meter->apartment_id);
                } elseif ($meter->tenant_id) {
                    $query->where('tenant_id', $meter->tenant_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->first();
    }

    protected function resolveChargeModels(MeterReading $reading, RentalAgreement $agreement): Collection
    {
        $utilityType = $reading->meter?->utility_type;

        $allowedModelIds = AgreementCharge::query()
            ->where('agreement_id', $agreement->id)
            ->where('status', AgreementCharge::STATUS_ACTIVE)
            ->whereHas('chargeModel', fn ($q) => $q
                ->where('pricing_strategy', ChargeModel::STRATEGY_METERED)
                ->when($utilityType, fn ($inner) => $inner->where('meter_type', $utilityType)))
            ->pluck('charge_model_id');

        if ($allowedModelIds->isEmpty()) {
            return collect();
        }

        return ChargeModel::query()
            ->where('company_id', $reading->company_id)
            ->whereIn('id', $allowedModelIds)
            ->where('status', ChargeModel::STATUS_ACTIVE)
            ->where('auto_generate', true)
            ->where('pricing_strategy', '!=', ChargeModel::STRATEGY_FORMULA)
            ->when($utilityType, fn ($q) => $q->where('meter_type', $utilityType))
            ->get()
            ->filter(fn (ChargeModel $model) => $model->isCurrentlyEffective())
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Single Charge
    |--------------------------------------------------------------------------
    */

    protected function generateCharge(MeterReading $reading, ChargeModel $model, ?RentalAgreement $agreement): Charge
    {
        $result = $this->calculator->calculate($model, [
            'consumption' => Money::toScale((string) $reading->consumption, 4),
            'meter_reading' => $reading,
        ]);

        $serviceStart = $reading->previous_reading_date 
            ? Carbon::parse($reading->previous_reading_date) 
            : Carbon::parse($reading->reading_date)->copy()->subMonth();

        $serviceEnd = Carbon::parse($reading->reading_date);

        // Fallback resolution path if an agreement isn't active (vacant unit cost management)
        $tenantId = $agreement ? $agreement->tenant_id : ($reading->tenant_id ?? $reading->meter?->tenant_id);
        $tenantName = $agreement
            ? $agreement->tenant?->full_display_name
            : ($reading->tenant?->full_display_name ?? $reading->meter?->tenant?->full_display_name);

        return Charge::create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'charge_number' => $this->generateChargeNumber(),
            'company_id' => $reading->company_id,
            'building_id' => $reading->building_id,
            'apartment_id' => $reading->apartment_id,
            
            // CRITICAL INTEGRATION COUPLING HOOKS
            'rental_agreement_id' => $agreement?->id, 
            'tenant_id'           => $tenantId,
            
            'charge_type_id' => $model->charge_type_id,
            'charge_model_id' => $model->id,
            'meter_reading_id' => $reading->id,

            'category' => Charge::CATEGORY_UTILITY,
            'billing_strategy' => $model->pricing_strategy,
            'status' => Charge::STATUS_PENDING,
            'currency' => $model->currency ?? 'USD',

            'quantity' => $reading->consumption,
            'unit_rate' => $model->unit_rate ?? 0.00,
            'subtotal_amount' => $result->subtotal,
            'tax_amount' => $result->taxAmount,
            'discount_amount' => 0.00,
            'total_amount' => $result->totalAmount,

            'meter_previous_reading' => $reading->previous_reading,
            'meter_current_reading' => $reading->current_reading,
            'meter_consumption' => $reading->consumption,

            'company_name_snapshot' => $reading->company?->name,
            'building_name_snapshot' => $reading->building?->name,
            'apartment_label_snapshot' => $reading->apartment?->label,
            'tenant_name_snapshot' => $tenantName,

            'service_period_start' => $serviceStart->toDateString(),
            'service_period_end' => $serviceEnd->toDateString(),

            'description' => sprintf('%s Utility Consumption Charge', $model->name),
            'notes' => sprintf(
                'Calculated via pricing strategy [%s] from meter reading reference ID: %s. Measured consumption volume: %s.%s',
                $model->pricing_strategy,
                $reading->id,
                $reading->consumption,
                $agreement ? '' : ' Warning: Processed during active asset vacancy.'
            ),
            'charged_at' => now(),
            'generated_by' => $reading->created_by,
        ]);
    }

    protected function ensureChargeNotGeneratedForModel(MeterReading $reading, ChargeModel $model): void
    {
        $exists = Charge::query()
            ->where('meter_reading_id', $reading->id)
            ->where('charge_model_id', $model->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'reading' => [
                    'A charge for this meter reading and charge model already exists.',
                ],
            ]);
        }
    }

    protected function generateChargeNumber(): string
    {
        return sprintf(
            'CHG-%s-%s',
            now()->format('Ym'),
            strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 8))
        );
    }
}