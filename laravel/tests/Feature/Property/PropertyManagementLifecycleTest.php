<?php

namespace Tests\Feature\Property;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyManagementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    public function test_draft_agreement_reserves_unit_and_activation_occupies_it(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_RENTAL,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);
        $tenant = Tenant::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $create = $this->postJson('/api/v1/rental-agreements', [
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->toDateString(),
            'monthly_rent' => 1200,
            'payment_due_day' => 5,
            'status' => Agreement::STATUS_DRAFT,
        ])->assertCreated();

        $agreementId = $create->json('data.id');

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_RESERVED, $apartment->inventory_status);

        $this->postJson("/api/v1/rental-agreements/{$agreementId}/activate")
            ->assertOk();

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_OCCUPIED, $apartment->inventory_status);
        $this->assertSame(Agreement::STATUS_ACTIVE, Agreement::find($agreementId)->status);
    }

    public function test_terminate_releases_unit(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_RENTAL,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $agreement = Agreement::factory()->withRentalAgreement()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        $apartment->update(['inventory_status' => Apartment::STATUS_OCCUPIED]);

        $this->postJson("/api/v1/rental-agreements/{$agreement->id}/terminate", [
            'termination_reason' => 'Lease ended by mutual consent.',
        ])->assertOk();

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_AVAILABLE, $apartment->inventory_status);
    }

    public function test_cannot_delete_building_with_apartments(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);

        $this->deleteJson("/api/v1/buildings/{$building->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'BUILDING_HAS_UNITS');
    }

    public function test_cannot_delete_tenant_with_draft_agreement(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        Agreement::factory()->withRentalAgreement()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_DRAFT,
        ]);

        $this->deleteJson("/api/v1/tenants/{$tenant->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'TENANT_HAS_LEASES');
    }

    public function test_approve_moves_draft_to_approved(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_RENTAL,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $create = $this->postJson('/api/v1/rental-agreements', [
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->toDateString(),
            'monthly_rent' => 1100,
            'payment_due_day' => 1,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->postJson("/api/v1/rental-agreements/{$id}/approve")
            ->assertOk()
            ->assertJsonPath('data.controls.can_activate', true);

        $this->assertSame(
            Agreement::STATUS_APPROVED,
            Agreement::find($id)->status
        );
    }

    public function test_second_draft_on_same_unit_is_rejected(): void
    {
        [$company] = $this->actingCompanyUser();

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_RENTAL,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);
        $tenantA = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $tenantB = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $this->postJson('/api/v1/rental-agreements', [
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenantA->id,
            'start_date' => now()->toDateString(),
            'monthly_rent' => 900,
            'payment_due_day' => 1,
        ])->assertCreated();

        $this->postJson('/api/v1/rental-agreements', [
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenantB->id,
            'start_date' => now()->toDateString(),
            'monthly_rent' => 950,
            'payment_due_day' => 1,
        ])->assertStatus(422);
    }

    public function test_sale_apartment_requires_market_sale_price(): void
    {
        [$company] = $this->actingCompanyUser();
        $building = Building::factory()->create(['company_id' => $company->id]);

        $this->postJson('/api/v1/apartments', [
            'building_id' => $building->id,
            'unit_number' => 'S-101',
            'property_type' => 'apartment',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'currency' => 'USD',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['market_sale_price']);
    }

    public function test_sale_apartment_creates_with_market_sale_price(): void
    {
        [$company] = $this->actingCompanyUser();
        $building = Building::factory()->create(['company_id' => $company->id]);

        $this->postJson('/api/v1/apartments', [
            'building_id' => $building->id,
            'unit_number' => 'S-102',
            'property_type' => 'apartment',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'market_sale_price' => 185000,
            'currency' => 'USD',
        ])->assertCreated()
            ->assertJsonPath('data.listing.listing_type', Apartment::LISTING_TYPE_SALE)
            ->assertJsonPath('data.pricing.market_sale_price', fn ($v) => (float) $v === 185000.0);
    }
}
