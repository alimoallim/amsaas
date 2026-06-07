<?php

namespace Tests\Feature\MeterReading;

use App\Models\Company;
use App\Models\Meter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeterReadingValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_422_when_required_fields_missing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['meter_id', 'reading_date', 'current_reading']);
    }

    public function test_store_returns_422_when_meter_is_not_operational(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_FAULTY,
            'current_reading' => 100,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => now()->toDateString(),
            'current_reading' => 150,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['meter']);
    }

    public function test_store_returns_422_when_current_reading_is_below_previous(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'initial_reading' => 100,
            'current_reading' => 100,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => now()->toDateString(),
            'current_reading' => 50,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_reading'])
            ->assertJsonPath('success', false);
    }
}
