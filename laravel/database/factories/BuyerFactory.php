<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BuyerFactory extends Factory
{
    protected $model = Buyer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'buyer_code' => 'BUY-'.strtoupper(Str::random(8)),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'national_id' => fake()->numerify('##########'),
            'nationality' => fake()->country(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'is_active' => true,
        ];
    }
}
