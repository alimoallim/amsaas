<?php

namespace Tests\Feature\MeterReading;

use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\Billing\GenerateChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MeterReadingBulkApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_approve_approves_verified_readings_and_skips_draft(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
        ]);

        $verified = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-01',
            'previous_reading' => 100,
            'current_reading' => 150,
            'consumption' => 50,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        $draft = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-02',
            'previous_reading' => 150,
            'current_reading' => 180,
            'consumption' => 30,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_DRAFT,
            'anomaly_detected' => true,
        ]);

        $mock = Mockery::mock(GenerateChargeService::class);
        $mock->shouldReceive('generateFromMeterReading')
            ->once()
            ->with(Mockery::on(fn ($reading) => $reading->id === $verified->id));
        $this->app->instance(GenerateChargeService::class, $mock);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings/bulk-approve', [
            'reading_ids' => [$verified->id, $draft->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.approved', 1)
            ->assertJsonPath('data.skipped', 1)
            ->assertJsonPath('data.failed', 0);

        $this->assertSame(MeterReading::STATUS_APPROVED, $verified->fresh()->status);
        $this->assertSame(MeterReading::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_bulk_approve_returns_422_when_no_readings_approved(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create(['company_id' => $company->id]);

        $draft = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => '2026-06-03',
            'previous_reading' => 10,
            'current_reading' => 20,
            'consumption' => 10,
            'status' => MeterReading::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/meter-readings/bulk-approve', [
            'reading_ids' => [$draft->id],
        ])
            ->assertStatus(422)
            ->assertJsonPath('data.approved', 0)
            ->assertJsonPath('data.skipped', 1);
    }
}
