<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeterReadingIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_not_found_for_other_company_meter_reading(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $meterB = Meter::factory()->create(['company_id' => $companyB->id]);

        $readingB = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $companyB->id,
            'meter_id' => $meterB->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 10,
            'consumption' => 10,
            'status' => MeterReading::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/meter-readings/{$readingB->id}")
            ->assertNotFound();
    }
}
