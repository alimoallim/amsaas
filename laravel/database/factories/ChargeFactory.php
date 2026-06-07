<?php

namespace Database\Factories;

use App\Models\Charge;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChargeFactory extends Factory
{
    protected $model = Charge::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'charge_number' => strtoupper(fake()->unique()->bothify('CHG-####')),
            'company_id' => Company::factory(),
            'category' => Charge::CATEGORY_UTILITY,
            'billing_strategy' => 'metered',
            'status' => Charge::STATUS_PENDING,
            'currency' => 'USD',
            'quantity' => 10,
            'unit_rate' => 2,
            'subtotal_amount' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'charged_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => Charge::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
