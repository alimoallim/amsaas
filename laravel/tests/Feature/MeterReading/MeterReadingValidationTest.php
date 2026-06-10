<?php

namespace Tests\Feature\MeterReading;

use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_store_returns_422_when_duplicate_meter_and_reading_date(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'current_reading' => 120,
            'initial_reading' => 100,
        ]);

        MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-07',
            'previous_reading' => 120,
            'current_reading' => 130,
            'consumption' => 10,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-07',
            'current_reading' => 145,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reading_date']);
    }

    public function test_approved_reading_can_be_updated_when_charges_not_invoiced(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'current_reading' => 130,
            'initial_reading' => 120,
        ]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-07',
            'previous_reading' => 120,
            'current_reading' => 130,
            'consumption' => 10,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/meter-readings/{$reading->id}", [
            'current_reading' => 135,
        ])
            ->assertOk()
            ->assertJsonPath('data.reading.current_reading', 135)
            ->assertJsonPath('data.status.value', MeterReading::STATUS_VERIFIED)
            ->assertJsonPath('data.controls.can_approve', true);

        $reading->refresh();
        $this->assertEquals(MeterReading::STATUS_VERIFIED, $reading->status);
        $this->assertNull($reading->approved_at);
    }
}
