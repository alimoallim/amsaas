<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'code' => (string) fake()->unique()->numberBetween(5000, 5999),
            'name' => fake()->words(3, true),
            'type' => Account::TYPE_EXPENSE,
            'description' => null,
            'is_system' => false,
            'sort_order' => 0,
            'status' => Account::STATUS_ACTIVE,
        ];
    }
}
