<?php

namespace Database\Factories;

use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChargeModelFactory extends Factory
{
    protected $model = ChargeModel::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'charge_type_id' => ChargeType::factory(),
            'code' => strtoupper(fake()->unique()->bothify('CM-###')),
            'name' => fake()->words(3, true),
            'currency' => 'USD',
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'billing_frequency' => 'monthly',
            'meter_type' => 'electricity',
            'unit_rate' => '1.5000',
            'auto_generate' => true,
            'status' => ChargeModel::STATUS_ACTIVE,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'taxable' => false,
            'tax_rate' => 0,
        ];
    }
}
