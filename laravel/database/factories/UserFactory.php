<?php

namespace Database\Factories;

use App\Models\Company; // Ensure this import is present
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'company_id' => Company::factory(), // Add this line
            // Plain text — User model "hashed" cast hashes using current BCRYPT_ROUNDS (see phpunit.xml).
            'password' => 'password',
            'role' => 'viewer',
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }
}