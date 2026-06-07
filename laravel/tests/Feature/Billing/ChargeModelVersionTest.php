<?php

namespace Tests\Feature\Billing;

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

class ChargeModelVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_formula_pricing_strategy(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/charge-models', [
            'charge_type_id' => $chargeType->id,
            'code' => 'FORMULA-1',
            'name' => 'Formula model',
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_FORMULA,
            'billing_frequency' => ChargeModel::FREQUENCY_MONTHLY,
            'formula_expression' => 'consumption * 2',
            'effective_from' => now()->toDateString(),
            'status' => ChargeModel::STATUS_DRAFT,
            'proration_enabled' => false,
            'late_fee_enabled' => false,
            'taxable' => false,
            'auto_generate' => true,
            'requires_approval' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pricing_strategy']);
    }

    public function test_update_active_in_use_model_creates_new_version(): void
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

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 500,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $model = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'code' => 'ELEC-METER',
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => 'electricity',
            'unit_rate' => '2.5000',
            'status' => ChargeModel::STATUS_ACTIVE,
            'effective_from' => now()->subMonth()->toDateString(),
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

        Sanctum::actingAs($user);

        $futureStart = now()->addMonth()->startOfMonth()->toDateString();

        $response = $this->putJson("/api/v1/charge-models/{$model->id}", [
            'charge_type_id' => $chargeType->id,
            'code' => 'ELEC-METER',
            'name' => 'Electricity metered v2',
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'billing_frequency' => ChargeModel::FREQUENCY_MONTHLY,
            'meter_type' => 'electricity',
            'unit_rate' => 3.25,
            'effective_from' => $futureStart,
            'effective_to' => null,
            'status' => ChargeModel::STATUS_ACTIVE,
            'proration_enabled' => false,
            'late_fee_enabled' => false,
            'taxable' => false,
            'auto_generate' => true,
            'requires_approval' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('versioned', true);

        $model->refresh();
        $this->assertSame(ChargeModel::STATUS_INACTIVE, $model->status);
        $this->assertNotNull($model->effective_to);

        $newId = $response->json('data.id');
        $this->assertNotEquals($model->id, $newId);
        $this->assertDatabaseHas('charge_models', [
            'id' => $newId,
            'unit_rate' => 3.25,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);
    }

    public function test_clone_creates_draft_copy_with_unique_code(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        $model = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'code' => 'WATER-METER',
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/charge-models/{$model->id}/clone")
            ->assertCreated()
            ->assertJsonPath('data.code', 'WATER-METER-COPY')
            ->assertJsonPath('data.status', ChargeModel::STATUS_DRAFT);
    }
}
