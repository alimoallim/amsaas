<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'company_id' => Company::factory(),
            'tenant_code' => strtoupper(fake()->unique()->bothify('TEN-####')),
            'tenant_type' => 'individual',
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => "{$first} {$last}",
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
