<?php

namespace Tests\Feature\Tenancy;

use App\Models\Charge;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChargeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_not_found_for_other_company_charge(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $chargeB = Charge::factory()->create(['company_id' => $companyB->id]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/charges/{$chargeB->id}")
            ->assertNotFound();
    }
}
