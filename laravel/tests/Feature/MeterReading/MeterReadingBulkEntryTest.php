<?php

namespace Tests\Feature\MeterReading;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeterReadingBulkEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_grid_returns_operational_meters_with_previous_reading(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'unit_number' => '101',
        ]);
        $tenant = Tenant::factory()->create([
            'company_id' => $company->id,
            'display_name' => 'Ahmed Hassan',
        ]);

        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'utility_type' => Meter::UTILITY_WATER,
            'initial_reading' => 1000,
            'current_reading' => 1180,
        ]);

        MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'reading_date' => '2026-05-01',
            'previous_reading' => 1000,
            'current_reading' => 1180,
            'consumption' => 180,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_APPROVED,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/meter-readings/entry-grid?'.http_build_query([
            'reading_date' => '2026-06-01',
            'building_id' => $building->id,
            'utility_type' => Meter::UTILITY_WATER,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.meter_id', $meter->id)
            ->assertJsonPath('data.0.unit_number', '101')
            ->assertJsonPath('data.0.tenant_name', 'Ahmed Hassan')
            ->assertJsonPath('data.0.previous_reading', '1180.0000');
    }

    public function test_bulk_store_skips_empty_readings_and_saves_entered_rows(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $meterA = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'initial_reading' => 100,
            'current_reading' => 100,
        ]);

        $meterB = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'initial_reading' => 200,
            'current_reading' => 200,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/meter-readings/bulk', [
            'reading_date' => '2026-06-07',
            'readings' => [
                ['meter_id' => $meterA->id, 'current_reading' => 150],
                ['meter_id' => $meterB->id, 'current_reading' => ''],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.saved', 1)
            ->assertJsonPath('data.skipped', 1)
            ->assertJsonPath('data.failed', 0);

        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $meterA->id,
            'current_reading' => 150,
            'consumption' => 50,
        ]);

        $this->assertDatabaseMissing('meter_readings', [
            'meter_id' => $meterB->id,
            'reading_date' => '2026-06-07',
        ]);
    }

    public function test_bulk_store_updates_existing_editable_reading(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'initial_reading' => 100,
            'current_reading' => 120,
        ]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-07',
            'previous_reading' => 100,
            'current_reading' => 120,
            'consumption' => 20,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings/bulk', [
            'reading_date' => '2026-06-07',
            'readings' => [
                ['meter_id' => $meter->id, 'current_reading' => 135],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.saved', 1);

        $this->assertDatabaseHas('meter_readings', [
            'id' => $reading->id,
            'current_reading' => 135,
            'consumption' => 35,
        ]);
    }

    public function test_bulk_store_reports_per_row_validation_failure(): void
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

        $this->postJson('/api/v1/meter-readings/bulk', [
            'reading_date' => '2026-06-07',
            'readings' => [
                ['meter_id' => $meter->id, 'current_reading' => 50],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('data.saved', 0)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.results.0.status', 'failed');
    }
}
