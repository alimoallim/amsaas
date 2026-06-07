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

class MeterReadingApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_dispatches_charge_generation(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'status' => Meter::STATUS_ACTIVE,
            'current_reading' => 100,
        ]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 100,
            'current_reading' => 150,
            'consumption' => 50,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_VERIFIED,
        ]);

        $mock = Mockery::mock(GenerateChargeService::class);
        $mock->shouldReceive('generateFromMeterReading')
            ->once()
            ->with(Mockery::on(fn ($r) => $r->id === $reading->id));
        $this->app->instance(GenerateChargeService::class, $mock);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/meter-readings/{$reading->id}/approve")
            ->assertOk();

        $this->assertEquals(
            MeterReading::STATUS_APPROVED,
            $reading->fresh()->status
        );
    }

    public function test_approve_returns_422_when_already_approved(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $meter = Meter::factory()->create(['company_id' => $company->id]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 10,
            'consumption' => 10,
            'status' => MeterReading::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/meter-readings/{$reading->id}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reading']);
    }
}
