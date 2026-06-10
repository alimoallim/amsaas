<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingCloseConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_generate_produces_draft_invoices_for_active_leases(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        foreach ([650, 550] as $rent) {
            $apartment = Apartment::factory()->create([
                'company_id' => $company->id,
                'building_id' => $building->id,
            ]);
            $tenant = Tenant::factory()->create(['company_id' => $company->id]);

            $this->postJson('/api/v1/rental-agreements', [
                'apartment_id' => $apartment->id,
                'tenant_id' => $tenant->id,
                'start_date' => now()->startOfMonth()->toDateString(),
                'monthly_rent' => $rent,
                'security_deposit' => 0,
                'payment_due_day' => 1,
                'currency' => 'USD',
                'status' => Agreement::STATUS_ACTIVE,
            ])->assertCreated();
        }

        $year = now()->year;
        $month = now()->month;

        $response = $this->postJson('/api/v1/billing/generate', [
            'year' => $year,
            'month' => $month,
            'generate_recurring' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('results.failed', 0);

        $draftCount = MonthlyInvoice::query()
            ->where('company_id', $company->id)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->where('status', 'draft')
            ->count();

        $this->assertEquals(2, $draftCount);

        $totalRent = (float) MonthlyInvoice::query()
            ->where('company_id', $company->id)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->sum('subtotal_rent');

        $this->assertEqualsWithDelta(1200, $totalRent, 0.01);
    }
}
