<?php

namespace Tests\Unit\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Building;
use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Services\Billing\AgreementUtilityUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementUtilityUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summarize_includes_latest_water_reading_for_agreement_unit(): void
    {
        $company = Company::factory()->create();
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 1000,
            'security_deposit' => 500,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $waterModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => Meter::UTILITY_WATER,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $waterModel->id,
            'charge_type_id' => $chargeType->id,
            'billing_start_date' => now()->toDateString(),
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'utility_type' => Meter::UTILITY_WATER,
            'ownership_type' => Meter::OWNERSHIP_APARTMENT,
            'measurement_unit' => 'm3',
            'status' => Meter::STATUS_ACTIVE,
        ]);

        MeterReading::query()->create([
            'id' => (string) str()->uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 100,
            'current_reading' => 125.5,
            'consumption' => 25.5,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        $rental = RentalAgreement::query()->with('agreement.apartment')->find($agreement->id);

        $summary = app(AgreementUtilityUsageService::class)->summarize($rental);

        $this->assertCount(1, $summary['items']);
        $this->assertSame('water', $summary['items'][0]['utility_type']);
        $this->assertEquals(25.5, $summary['items'][0]['consumption']);
        $this->assertSame('m3', $summary['items'][0]['measurement_unit']);
    }
}
