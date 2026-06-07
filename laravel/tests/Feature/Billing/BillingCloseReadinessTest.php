<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
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

class BillingCloseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_shows_active_leases_and_can_compile_without_pending_items(): void
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
        ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/rental-agreements', [
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->startOfMonth()->toDateString(),
            'monthly_rent' => 650,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'currency' => 'USD',
            'status' => Agreement::STATUS_ACTIVE,
        ])->assertCreated();

        $year = now()->year;
        $month = now()->month;

        $this->getJson("/api/v1/billing/summary?year={$year}&month={$month}")
            ->assertOk()
            ->assertJsonPath('metrics.active_rental_agreements', 1)
            ->assertJsonPath('metrics.can_compile', true);
    }
}
