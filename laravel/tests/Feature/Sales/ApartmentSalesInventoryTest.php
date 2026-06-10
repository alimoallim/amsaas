<?php

namespace Tests\Feature\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\ApartmentInventoryStatusLog;
use App\Models\Building;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Property\ApartmentInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApartmentSalesInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    public function test_cannot_reserve_for_sale_when_unit_has_active_lease(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_OCCUPIED,
        ]);

        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        Agreement::factory()->withRentalAgreement()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'agreement_type' => Agreement::TYPE_RENTAL,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        $service = app(ApartmentInventoryService::class);

        $this->expectException(BusinessRuleException::class);
        $service->markReservedForSale($apartment);
    }

    public function test_transition_logs_status_history(): void
    {
        [$company, $user] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'lock_version' => 0,
        ]);

        app(ApartmentInventoryService::class)->markReservedForSale($apartment);

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_RESERVED, $apartment->inventory_status);
        $this->assertSame(1, $apartment->lock_version);

        $this->assertDatabaseHas('apartment_inventory_status_logs', [
            'apartment_id' => $apartment->id,
            'from_status' => Apartment::STATUS_AVAILABLE,
            'to_status' => Apartment::STATUS_RESERVED,
            'changed_by' => $user->id,
        ]);
    }

    public function test_optimistic_lock_conflict_on_stale_version(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'lock_version' => 0,
        ]);

        $stale = clone $apartment;
        app(ApartmentInventoryService::class)->markReservedForSale($apartment);

        $this->expectException(BusinessRuleException::class);
        app(ApartmentInventoryService::class)->transitionStatus(
            $stale,
            Apartment::STATUS_UNDER_CONTRACT,
            'Stale update',
        );
    }

    public function test_inventory_available_endpoint_lists_sale_units(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'market_sale_price' => 120000,
        ]);
        Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_RENTAL,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);

        $this->getJson('/api/v1/inventory/available?sellable_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_inventory_history_endpoint_returns_logs(): void
    {
        [$company, $user] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);

        ApartmentInventoryStatusLog::create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'from_status' => Apartment::STATUS_AVAILABLE,
            'to_status' => Apartment::STATUS_RESERVED,
            'reason' => 'Test',
            'changed_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->getJson("/api/v1/apartments/{$apartment->id}/inventory-history")
            ->assertOk()
            ->assertJsonPath('data.0.to_status', Apartment::STATUS_RESERVED);
    }
}
