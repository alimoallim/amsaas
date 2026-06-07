<?php

namespace Tests\Feature\Property;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RentalAgreementUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_agreement_update_accepts_billing_without_start_date(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        $utilityModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => 'electricity',
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 650,
            'security_deposit' => 0,
            'payment_due_day' => 5,
            'billing_cycle' => 'monthly',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/rental-agreements/{$agreement->id}", [
            'monthly_rent' => 650,
            'payment_due_day' => 5,
            'currency' => 'USD',
            'auto_renew' => false,
            'renewal_notice_days' => 30,
            'recurring_charges' => [
                [
                    'charge_model_id' => $utilityModel->id,
                    'override_unit_rate' => 0.15,
                    'custom_name' => 'Electricity',
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('agreement_charges', [
            'agreement_id' => $agreement->id,
            'charge_model_id' => $utilityModel->id,
        ]);

        $this->assertEquals(
            1,
            AgreementCharge::where('agreement_id', $agreement->id)
                ->where('charge_model_id', $utilityModel->id)
                ->count()
        );
    }

    public function test_adding_utility_when_deposit_exists_does_not_duplicate_charge_model(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        $depositModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_FLAT_FEE,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $utilityModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => 'electricity',
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
            'start_date' => '2026-02-01',
            'end_date' => now()->addYear()->toDateString(),
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 550,
            'security_deposit' => 550,
            'payment_due_day' => 5,
            'billing_cycle' => 'monthly',
        ]);

        $depositCharge = AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $depositModel->id,
            'charge_type_id' => $chargeType->id,
            'override_amount' => 550,
            'billing_start_date' => '2026-02-01',
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $staleRow = AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $utilityModel->id,
            'charge_type_id' => $chargeType->id,
            'billing_start_date' => '2026-02-01',
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/rental-agreements/{$agreement->id}", [
            'monthly_rent' => 550,
            'payment_due_day' => 5,
            'currency' => 'USD',
            'auto_renew' => false,
            'renewal_notice_days' => 30,
            'recurring_charges' => [
                [
                    'id' => $staleRow->id,
                    'charge_model_id' => $depositModel->id,
                    'override_amount' => 550,
                ],
                [
                    'charge_model_id' => $utilityModel->id,
                    'override_unit_rate' => 0.15,
                    'custom_name' => 'Electricity',
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('agreement_charges', [
            'id' => $depositCharge->id,
            'charge_model_id' => $depositModel->id,
            'override_amount' => 550,
        ]);

        $this->assertDatabaseMissing('agreement_charges', ['id' => $staleRow->id]);

        $this->assertDatabaseHas('agreement_charges', [
            'agreement_id' => $agreement->id,
            'charge_model_id' => $utilityModel->id,
            'custom_name' => 'Electricity',
            'deleted_at' => null,
        ]);

        $this->assertEquals(
            2,
            AgreementCharge::where('agreement_id', $agreement->id)->count()
        );
    }

    public function test_re_adding_soft_deleted_utility_charge_restores_row(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        $utilityModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => 'electricity',
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
            'start_date' => '2026-05-01',
            'end_date' => now()->addYear()->toDateString(),
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 550,
            'security_deposit' => 0,
            'payment_due_day' => 5,
            'billing_cycle' => 'monthly',
        ]);

        $electricityCharge = AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $utilityModel->id,
            'charge_type_id' => $chargeType->id,
            'override_unit_rate' => 1.0,
            'billing_start_date' => '2026-05-01',
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $electricityCharge->delete();

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/rental-agreements/{$agreement->id}", [
            'monthly_rent' => 550,
            'payment_due_day' => 5,
            'currency' => 'USD',
            'auto_renew' => false,
            'renewal_notice_days' => 30,
            'recurring_charges' => [
                [
                    'charge_model_id' => $utilityModel->id,
                    'override_unit_rate' => 1.5,
                    'custom_name' => 'ELECTRICITY',
                ],
            ],
        ]);

        $response->assertOk();

        $electricityCharge->refresh();

        $this->assertNull($electricityCharge->deleted_at);
        $this->assertEquals(1.5, (float) $electricityCharge->override_unit_rate);
        $this->assertEquals(
            1,
            AgreementCharge::where('agreement_id', $agreement->id)->count()
        );
    }
}
