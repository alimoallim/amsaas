<?php

namespace Database\Factories;

use App\Models\ChargeType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChargeTypeFactory extends Factory
{
    protected $model = ChargeType::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'code' => strtoupper(fake()->unique()->bothify('CT-###')),
            'name' => fake()->words(2, true),
            'category' => ChargeType::CATEGORY_UTILITY,
            'billing_behavior' => ChargeType::BILLING_METERED,
            'calculation_method' => ChargeType::CALCULATION_PER_UNIT,
            'billing_frequency' => ChargeType::FREQUENCY_MONTHLY,
            'financial_classification' => ChargeType::CLASSIFICATION_INCOME,
            'status' => ChargeType::STATUS_ACTIVE,
            'sort_order' => 0,
        ];
    }
}
