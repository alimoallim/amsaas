<?php

namespace Tests\Feature\Meter;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\Meter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeterBuildingScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_by_building_includes_tenant_owned_meter_without_building_id(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $company->id]);

        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);

        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        $tenantMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'ownership_type' => Meter::OWNERSHIP_TENANT,
            'tenant_id' => $tenant->id,
            'building_id' => null,
            'apartment_id' => null,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $otherTenantMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'ownership_type' => Meter::OWNERSHIP_TENANT,
            'tenant_id' => Tenant::factory()->create(['company_id' => $company->id])->id,
            'building_id' => null,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $buildingMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'ownership_type' => Meter::OWNERSHIP_BUILDING,
            'building_id' => $building->id,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $wrongBuildingMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'ownership_type' => Meter::OWNERSHIP_BUILDING,
            'building_id' => $otherBuilding->id,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/meters?'.http_build_query([
            'building_id' => $building->id,
            'status' => 'active',
        ]));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($tenantMeter->id, $ids);
        $this->assertContains($buildingMeter->id, $ids);
        $this->assertNotContains($otherTenantMeter->id, $ids);
        $this->assertNotContains($wrongBuildingMeter->id, $ids);
    }

    public function test_index_by_building_includes_apartment_meter_via_unit(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);

        $apartmentMeter = Meter::factory()->create([
            'company_id' => $company->id,
            'ownership_type' => Meter::OWNERSHIP_APARTMENT,
            'apartment_id' => $apartment->id,
            'building_id' => $building->id,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/meters?'.http_build_query([
            'building_id' => $building->id,
            'status' => 'active',
        ]));

        $response->assertOk();
        $this->assertContains(
            $apartmentMeter->id,
            collect($response->json('data'))->pluck('id')->all()
        );
    }
}
