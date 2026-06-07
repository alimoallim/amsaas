<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Meter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeterIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_not_found_for_other_company_meter(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $meterB = Meter::factory()->create([
            'company_id' => $companyB->id,
        ]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/meters/{$meterB->id}")
            ->assertNotFound();
    }
}
