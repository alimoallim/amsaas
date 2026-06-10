<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\ChargeModel;
use App\Models\Company;
use App\Models\Meter;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\BillingCompanyBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCompanyBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_creates_rent_model_and_fixes_water_meter_type(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
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
            'monthly_rent' => 500,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $waterModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'name' => 'Water Consumption',
            'code' => 'WATER-METER',
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'meter_type' => Meter::UTILITY_ELECTRICITY,
            'auto_generate' => true,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $service = app(BillingCompanyBootstrapService::class);
        $result = $service->bootstrap($company, $user, now()->startOfMonth(), false, false);

        $this->assertNotNull($result['rent_charge_model']);

        $this->assertDatabaseHas('charge_models', [
            'company_id' => $company->id,
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $waterModel->refresh();
        $this->assertEquals(Meter::UTILITY_WATER, $waterModel->meter_type);
        $this->assertSame(1, $result['fixed_charge_models']);
    }
}
