<?php

namespace Tests\Feature\Tenancy;

use App\Models\ChargeModel;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChargeModelIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_excludes_other_company_charge_models(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        ChargeModel::factory()->create([
            'company_id' => $companyA->id,
            'code' => 'CM-A',
            'name' => 'Company A Model',
        ]);

        ChargeModel::factory()->create([
            'company_id' => $companyB->id,
            'code' => 'CM-B',
            'name' => 'Company B Model',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/charge-models');

        $response->assertOk();
        $response->assertJsonFragment(['code' => 'CM-A']);
        $response->assertJsonMissing(['code' => 'CM-B']);
    }

    public function test_show_returns_not_found_for_other_company_charge_model(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $modelB = ChargeModel::factory()->create([
            'company_id' => $companyB->id,
            'code' => 'CM-B',
        ]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/charge-models/{$modelB->id}")
            ->assertNotFound();
    }
}
