<?php

namespace Tests\Unit\Billing;

use App\Models\ChargeModel;
use App\Services\Billing\CalculateChargeService;
use App\Services\Billing\Exceptions\ChargeCalculationException;
use Carbon\Carbon;
use Tests\TestCase;

class CalculateChargeServiceTest extends TestCase
{
    private CalculateChargeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalculateChargeService;
    }

    public function test_metered_strategy_uses_bcmath(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'unit_rate' => '2.5000',
        ]);

        $result = $this->service->calculate($model, ['consumption' => '10']);

        $this->assertEquals(25.0, $result->subtotal);
    }

    public function test_tiered_strategy_accumulates_brackets(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_TIERED,
            'tier_configuration' => [
                ['from' => 0, 'to' => 50, 'rate' => 1],
                ['from' => 50, 'to' => null, 'rate' => 2],
            ],
        ]);

        $result = $this->service->calculate($model, ['consumption' => '100']);

        $this->assertEquals(150.0, $result->subtotal);
    }

    public function test_tax_is_calculated_with_bcmath(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_FIXED,
            'base_amount' => '100.00',
            'taxable' => true,
            'tax_rate' => '10',
        ]);

        $result = $this->service->calculate($model, []);

        $this->assertEquals(100.0, $result->subtotal);
        $this->assertEquals(10.0, $result->taxAmount);
        $this->assertEquals(110.0, $result->totalAmount);
    }

    public function test_minimum_amount_clamps_subtotal(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_FIXED,
            'base_amount' => '5.00',
            'minimum_amount' => '20.00',
        ]);

        $result = $this->service->calculate($model, []);

        $this->assertEquals(20.0, $result->subtotal);
    }

    public function test_agreement_rent_uses_monthly_rent_from_context(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
        ]);

        $result = $this->service->calculate($model, ['monthly_rent' => '1500.00']);

        $this->assertEquals(1500.0, $result->subtotal);
    }

    public function test_flat_fee_uses_agreement_override_amount(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_FLAT_FEE,
        ]);

        $result = $this->service->calculate($model, ['override_amount' => '75.50']);

        $this->assertEquals(75.5, $result->subtotal);
    }

    public function test_formula_strategy_remains_unimplemented(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_FORMULA,
        ]);

        $this->expectException(ChargeCalculationException::class);

        $this->service->calculate($model);
    }

    public function test_percentage_strategy_calculates_from_base_amount(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_PERCENTAGE,
            'percentage_rate' => '10',
        ]);

        $result = $this->service->calculate($model, ['base_amount' => '1000']);

        $this->assertEquals(100.0, $result->subtotal);
    }

    public function test_metered_zero_consumption_yields_zero_charge(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'unit_rate' => '3.5000',
        ]);

        $result = $this->service->calculate($model, ['consumption' => '0']);

        $this->assertEquals(0.0, $result->subtotal);
    }

    public function test_maximum_amount_clamps_subtotal(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_FIXED,
            'base_amount' => '500.00',
            'maximum_amount' => '100.00',
        ]);

        $result = $this->service->calculate($model, []);

        $this->assertEquals(100.0, $result->subtotal);
    }

    public function test_inactive_model_cannot_be_calculated(): void
    {
        $model = $this->activeChargeModel([
            'pricing_strategy' => ChargeModel::STRATEGY_FIXED,
            'base_amount' => '50.00',
            'status' => ChargeModel::STATUS_INACTIVE,
        ]);

        $this->expectException(ChargeCalculationException::class);

        $this->service->calculate($model, []);
    }

    private function activeChargeModel(array $attributes): ChargeModel
    {
        $model = new ChargeModel;

        $model->forceFill(array_merge([
            'status' => ChargeModel::STATUS_ACTIVE,
            'effective_from' => Carbon::today()->subDay()->toDateString(),
            'effective_to' => null,
            'taxable' => false,
            'tax_rate' => 0,
            'currency' => 'USD',
        ], $attributes));

        return $model;
    }
}
