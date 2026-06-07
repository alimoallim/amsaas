<?php

namespace Tests\Feature\Tenancy;

use App\Models\ChargeType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChargeTypeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_excludes_other_company_charge_types(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        ChargeType::factory()->create([
            'company_id' => $companyA->id,
            'code' => 'CT-A',
            'name' => 'Company A Type',
        ]);

        ChargeType::factory()->create([
            'company_id' => $companyB->id,
            'code' => 'CT-B',
            'name' => 'Company B Type',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/charge-types');

        $response->assertOk();
        $response->assertJsonFragment(['code' => 'CT-A']);
        $response->assertJsonMissing(['code' => 'CT-B']);
    }

    public function test_show_returns_not_found_for_other_company_charge_type(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $chargeTypeB = ChargeType::factory()->create([
            'company_id' => $companyB->id,
            'code' => 'CT-B',
        ]);

        Sanctum::actingAs($userA);

        // Scoped binding returns 404; policy-only denial would be 403.
        $this->getJson("/api/v1/charge-types/{$chargeTypeB->id}")
            ->assertNotFound();
    }
}
