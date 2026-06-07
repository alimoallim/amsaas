<?php

namespace Tests\Feature\Tenancy;

use App\Models\Charge;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingOperationsIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_only_counts_authenticated_company_utility_charges(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $makeCharge = function (string $companyId, string $suffix, float $amount): void {
            Charge::withoutGlobalScopes()->create([
                'id' => (string) Str::uuid(),
                'uuid' => (string) Str::uuid(),
                'charge_number' => "CH-{$suffix}",
                'company_id' => $companyId,
                'category' => Charge::CATEGORY_UTILITY,
                'billing_strategy' => 'metered',
                'status' => Charge::STATUS_APPROVED,
                'currency' => 'USD',
                'total_amount' => $amount,
                'subtotal_amount' => $amount,
            ]);
        };

        $makeCharge($companyA->id, 'A', 100);
        $makeCharge($companyB->id, 'B', 500);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/billing/summary');

        $response->assertOk();
        $response->assertJsonPath('company_id', $companyA->id);
        $response->assertJsonPath('metrics.utility_items_ready', 1);
        $response->assertJsonPath('metrics.utility_items_revenue', 100);
    }
}
