<?php

namespace Tests\Feature\Billing;

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

class ManualInvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_invoice_from_rental_agreement_uses_monthly_rent(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'market_rent_price' => 400,
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
            'monthly_rent' => 650,
            'security_deposit' => 0,
            'payment_due_day' => 5,
            'billing_cycle' => 'monthly',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/invoices', [
            'apartment_id' => $apartment->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal_rent', fn ($value) => (float) $value === 650.0)
            ->assertJsonPath('data.contract_type', 'rental');

        $this->assertDatabaseHas('monthly_invoices', [
            'apartment_id' => $apartment->id,
            'contract_id' => $agreement->id,
            'subtotal_rent' => 650,
            'status' => 'draft',
        ]);
    }
}
