<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->company().' Tower',
            'code' => strtoupper(fake()->unique()->bothify('BLD-###')),
            'type' => 'residential',
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'operating_currency' => 'USD',
            'total_units' => 10,
            'total_floors' => 5,
            'is_active' => true,
        ];
    }
}
