<?php

namespace Tests\Feature\Billing;

use App\Models\Charge;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChargeIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_flat_data_array_for_frontend(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Charge::factory()->count(2)->create([
            'company_id' => $company->id,
            'category' => Charge::CATEGORY_UTILITY,
            'status' => Charge::STATUS_PENDING,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/charges?category=utility&status=pending');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'charge_number', 'status', 'amounts', 'controls'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_summary_returns_utility_counts(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Charge::factory()->create([
            'company_id' => $company->id,
            'category' => Charge::CATEGORY_UTILITY,
            'status' => Charge::STATUS_PENDING,
        ]);

        Charge::factory()->approved()->create([
            'company_id' => $company->id,
            'category' => Charge::CATEGORY_UTILITY,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/charges/summary')
            ->assertOk()
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.approved_ready', 1);
    }
}
