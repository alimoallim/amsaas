<?php

namespace Tests\Feature\Dashboard;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_portfolio_and_financial_metrics_for_company(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Properties']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Jane Admin',
        ]);

        $building = Building::factory()->create(['company_id' => $company->id]);
        Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'inventory_status' => Apartment::STATUS_OCCUPIED,
        ]);
        Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);

        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'agreement_type' => Agreement::TYPE_RENTAL,
            'status' => Agreement::STATUS_ACTIVE,
            'tenant_id' => $tenant->id,
            'apartment_id' => $building->apartments()->first()->id,
        ]);

        MonthlyInvoice::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $building->apartments()->first()->id,
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'status' => 'issued',
            'subtotal_rent' => 800,
            'subtotal_utilities' => 120,
            'paid_amount' => 500,
        ]);

        Payment::query()->create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'receipt_number' => 'RCPT-DASH-001',
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
            'payment_method' => 'bank_transfer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.name', 'Jane Admin')
            ->assertJsonPath('data.user.company', 'Acme Properties')
            ->assertJsonPath('data.portfolio.buildings', 1)
            ->assertJsonPath('data.portfolio.apartments', 2)
            ->assertJsonPath('data.portfolio.occupied', 1)
            ->assertJsonPath('data.portfolio.vacant', 1)
            ->assertJsonPath('data.portfolio.tenants', 1)
            ->assertJsonPath('data.portfolio.leases', 1)
            ->assertJsonPath('data.financials.rent_revenue', 800)
            ->assertJsonPath('data.financials.utility_revenue', 120)
            ->assertJsonPath('data.financials.collected_mtd', 500)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'portfolio',
                    'financials',
                    'collections' => ['aging_buckets', 'delinquency'],
                    'operations',
                    'alerts',
                ],
            ]);

        $this->assertSame(50.0, (float) $response->json('data.portfolio.occupancy_rate'));
        $this->assertGreaterThan(0, (float) $response->json('data.financials.outstanding_receivables'));
    }

    public function test_dashboard_is_scoped_to_authenticated_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        Building::factory()->count(2)->create(['company_id' => $companyA->id]);
        Building::factory()->count(5)->create(['company_id' => $companyB->id]);

        Sanctum::actingAs($userA);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.portfolio.buildings', 2);
    }
}
