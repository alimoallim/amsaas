<?php

namespace Tests\Feature\Tenancy;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApartmentIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_not_found_for_other_company_apartment(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $buildingB = Building::factory()->create(['company_id' => $companyB->id]);
        $apartmentB = Apartment::factory()->create([
            'company_id' => $companyB->id,
            'building_id' => $buildingB->id,
        ]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/apartments/{$apartmentB->id}")
            ->assertNotFound();
    }
}
