<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\AgreementUtilityUsageService;
use App\Services\Billing\GenerateChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeterTenantAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_meter_reading_does_not_generate_tenant_charge(): void
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
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 650,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $steamModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'meter_type' => Meter::UTILITY_STEAM,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'auto_generate' => true,
        ]);

        AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $steamModel->id,
            'charge_type_id' => $chargeType->id,
            'billing_start_date' => $agreement->start_date,
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $buildingMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => null,
            'tenant_id' => null,
            'ownership_type' => Meter::OWNERSHIP_BUILDING,
            'utility_type' => Meter::UTILITY_STEAM,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $buildingMeter->id,
            'building_id' => $building->id,
            'apartment_id' => null,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'consumption' => 100,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $charges = app(GenerateChargeService::class)->generateFromMeterReading($reading);

        $this->assertCount(0, $charges);
        $this->assertDatabaseCount('charges', 0);
    }

    public function test_building_meter_not_shown_on_other_tenant_agreement(): void
    {
        $company = Company::factory()->create();
        $building = Building::factory()->create(['company_id' => $company->id]);

        $apartmentA = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $apartmentB = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);

        $tenantA = Tenant::factory()->create(['company_id' => $company->id]);
        $tenantB = Tenant::factory()->create(['company_id' => $company->id]);

        $agreementA = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartmentA->id,
            'tenant_id' => $tenantA->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);
        $agreementB = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartmentB->id,
            'tenant_id' => $tenantB->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        RentalAgreement::query()->create([
            'id' => $agreementA->id,
            'monthly_rent' => 500,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);
        RentalAgreement::query()->create([
            'id' => $agreementB->id,
            'monthly_rent' => 600,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $electricityModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => Meter::UTILITY_ELECTRICITY,
        ]);

        foreach ([$agreementA, $agreementB] as $agreement) {
            AgreementCharge::query()->create([
                'company_id' => $company->id,
                'agreement_id' => $agreement->id,
                'charge_model_id' => $electricityModel->id,
                'charge_type_id' => $chargeType->id,
                'billing_start_date' => now()->toDateString(),
                'status' => AgreementCharge::STATUS_ACTIVE,
            ]);
        }

        $buildingMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => null,
            'ownership_type' => Meter::OWNERSHIP_BUILDING,
            'utility_type' => Meter::UTILITY_ELECTRICITY,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $unitMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartmentA->id,
            'ownership_type' => Meter::OWNERSHIP_APARTMENT,
            'utility_type' => Meter::UTILITY_ELECTRICITY,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        MeterReading::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $buildingMeter->id,
            'building_id' => $building->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 999,
            'consumption' => 999,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        MeterReading::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $unitMeter->id,
            'building_id' => $building->id,
            'apartment_id' => $apartmentA->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 12,
            'consumption' => 12,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        $summaryA = app(AgreementUtilityUsageService::class)
            ->summarize(RentalAgreement::with('agreement.apartment')->find($agreementA->id));
        $summaryB = app(AgreementUtilityUsageService::class)
            ->summarize(RentalAgreement::with('agreement.apartment')->find($agreementB->id));

        $this->assertCount(1, $summaryA['items']);
        $this->assertEquals(12, $summaryA['items'][0]['consumption']);
        $this->assertSame($unitMeter->id, $summaryA['items'][0]['meter_id']);

        $this->assertCount(0, $summaryB['items']);
    }

    public function test_unit_meter_charge_only_attaches_to_assigned_agreement(): void
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
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 650,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $model = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'meter_type' => Meter::UTILITY_ELECTRICITY,
            'unit_rate' => '2.0000',
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'auto_generate' => true,
        ]);

        AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $model->id,
            'charge_type_id' => $chargeType->id,
            'billing_start_date' => $agreement->start_date,
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'ownership_type' => Meter::OWNERSHIP_APARTMENT,
            'utility_type' => Meter::UTILITY_ELECTRICITY,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 25,
            'consumption' => 25,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $charges = app(GenerateChargeService::class)->generateFromMeterReading($reading);

        $this->assertCount(1, $charges);
        $this->assertDatabaseHas('charges', [
            'meter_reading_id' => $reading->id,
            'rental_agreement_id' => $agreement->id,
            'status' => Charge::STATUS_PENDING,
        ]);
    }
}
