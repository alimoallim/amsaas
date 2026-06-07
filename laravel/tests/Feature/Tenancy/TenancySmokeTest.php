<?php

namespace Tests\Feature\Tenancy;

use App\Models\Building;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\Meter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 0 manual-smoke equivalent: two tenants must never see each other's records on core APIs.
 */
class TenancySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_endpoints_hide_other_company_records(): void
    {
        $companyA = Company::factory()->create(['name' => 'Tenant A']);
        $companyB = Company::factory()->create(['name' => 'Tenant B']);

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $buildingB = Building::factory()->create(['company_id' => $companyB->id, 'name' => 'B Tower']);
        $tenantB = Tenant::factory()->create(['company_id' => $companyB->id]);
        $meterB = Meter::factory()->create(['company_id' => $companyB->id, 'meter_number' => 'MTR-B-001']);
        $chargeTypeB = ChargeType::factory()->create(['company_id' => $companyB->id, 'code' => 'CT-B-SMOKE']);
        $chargeModelB = ChargeModel::factory()->create(['company_id' => $companyB->id, 'code' => 'CM-B-SMOKE']);

        Building::factory()->create(['company_id' => $companyA->id, 'name' => 'A Tower']);
        ChargeType::factory()->create(['company_id' => $companyA->id, 'code' => 'CT-A-SMOKE']);

        Sanctum::actingAs($userA);

        $this->getJson('/api/v1/buildings')
            ->assertOk()
            ->assertJsonFragment(['name' => 'A Tower'])
            ->assertJsonMissing(['name' => 'B Tower']);

        $this->getJson('/api/v1/tenants')
            ->assertOk()
            ->assertJsonMissing(['id' => $tenantB->id]);

        $this->getJson('/api/v1/meters')
            ->assertOk()
            ->assertJsonMissing(['meter_number' => 'MTR-B-001']);

        $this->getJson('/api/v1/charge-types')
            ->assertOk()
            ->assertJsonFragment(['code' => 'CT-A-SMOKE'])
            ->assertJsonMissing(['code' => 'CT-B-SMOKE']);

        $this->getJson('/api/v1/charge-models')
            ->assertOk()
            ->assertJsonMissing(['code' => 'CM-B-SMOKE']);

        $this->getJson("/api/v1/buildings/{$buildingB->id}")->assertNotFound();
        $this->getJson("/api/v1/tenants/{$tenantB->id}")->assertNotFound();
        $this->getJson("/api/v1/meters/{$meterB->id}")->assertNotFound();
        $this->getJson("/api/v1/charge-types/{$chargeTypeB->id}")->assertNotFound();
        $this->getJson("/api/v1/charge-models/{$chargeModelB->id}")->assertNotFound();
    }
}
