<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgreementChargeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_rental_agreement_syncs_rent_and_flat_fee_charges(): void
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

        $rentModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $serviceModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_FLAT_FEE,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/rental-agreements', [
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->toDateString(),
            'monthly_rent' => 1200,
            'security_deposit' => 500,
            'payment_due_day' => 5,
            'currency' => 'USD',
            'status' => Agreement::STATUS_DRAFT,
            'rent_charge_model_id' => $rentModel->id,
            'recurring_charges' => [
                [
                    'charge_model_id' => $serviceModel->id,
                    'override_amount' => 75,
                    'custom_name' => 'Security',
                ],
            ],
        ]);

        $response->assertCreated();

        $agreementId = $response->json('data.id');

        $this->assertDatabaseHas('agreement_charges', [
            'agreement_id' => $agreementId,
            'charge_model_id' => $rentModel->id,
            'override_amount' => null,
        ]);

        $this->assertDatabaseHas('agreement_charges', [
            'agreement_id' => $agreementId,
            'charge_model_id' => $serviceModel->id,
            'override_amount' => 75,
        ]);

        $this->assertEquals(
            2,
            AgreementCharge::where('agreement_id', $agreementId)->count()
        );
    }
}
