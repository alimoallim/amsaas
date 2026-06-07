<?php

namespace Tests\Feature\Billing;

use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChargeModelStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_minimal_fixed_charge_model_payload(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $payload = [
            'charge_type_id' => $chargeType->id,
            'code' => 'RENT-FIXED',
            'name' => 'Monthly rent',
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'billing_frequency' => ChargeModel::FREQUENCY_MONTHLY,
            'effective_from' => now()->toDateString(),
            'status' => ChargeModel::STATUS_DRAFT,
            'proration_enabled' => false,
            'late_fee_enabled' => false,
            'taxable' => false,
            'auto_generate' => true,
            'requires_approval' => false,
        ];

        $response = $this->postJson('/api/v1/charge-models', $payload);

        $response->assertCreated();
        $response->assertJsonFragment(['code' => 'RENT-FIXED']);
        $this->assertDatabaseHas('charge_models', [
            'code' => 'RENT-FIXED',
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
        ]);
    }

    public function test_store_requires_effective_from(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $payload = [
            'charge_type_id' => $chargeType->id,
            'code' => 'NO-DATE',
            'name' => 'Missing date',
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'billing_frequency' => ChargeModel::FREQUENCY_MONTHLY,
            'status' => ChargeModel::STATUS_DRAFT,
            'proration_enabled' => false,
            'late_fee_enabled' => false,
            'taxable' => false,
            'auto_generate' => true,
            'requires_approval' => false,
        ];

        $this->postJson('/api/v1/charge-models', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['effective_from']);
    }

    public function test_store_tiered_requires_valid_tier_configuration(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/charge-models', [
            'charge_type_id' => $chargeType->id,
            'code' => 'TIER-BAD',
            'name' => 'Bad tiers',
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_TIERED,
            'billing_frequency' => ChargeModel::FREQUENCY_MONTHLY,
            'tier_configuration' => [
                ['from' => 0, 'to' => 50, 'rate' => 1],
                ['from' => 60, 'to' => 100, 'rate' => 2],
            ],
            'effective_from' => now()->toDateString(),
            'status' => ChargeModel::STATUS_DRAFT,
            'proration_enabled' => false,
            'late_fee_enabled' => false,
            'taxable' => false,
            'auto_generate' => true,
            'requires_approval' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tier_configuration']);
    }

    public function test_store_tiered_accepts_contiguous_tiers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/charge-models', [
            'charge_type_id' => $chargeType->id,
            'code' => 'TIER-OK',
            'name' => 'Good tiers',
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_TIERED,
            'billing_frequency' => ChargeModel::FREQUENCY_MONTHLY,
            'tier_configuration' => [
                ['from' => 0, 'to' => 50, 'rate' => 1],
                ['from' => 51, 'to' => null, 'rate' => 2],
            ],
            'effective_from' => now()->toDateString(),
            'status' => ChargeModel::STATUS_DRAFT,
            'proration_enabled' => false,
            'late_fee_enabled' => false,
            'taxable' => false,
            'auto_generate' => true,
            'requires_approval' => false,
        ])
            ->assertCreated()
            ->assertJsonFragment(['code' => 'TIER-OK']);
    }
}
