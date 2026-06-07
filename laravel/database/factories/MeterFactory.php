<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Meter;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterFactory extends Factory
{
    protected $model = Meter::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'meter_number' => fake()->unique()->bothify('MTR-#####'),
            'utility_type' => Meter::UTILITY_ELECTRICITY,
            'ownership_type' => Meter::OWNERSHIP_APARTMENT,
            'meter_type' => Meter::TYPE_DIGITAL,
            'measurement_unit' => Meter::UNIT_KWH,
            'initial_reading' => 0,
            'current_reading' => 0,
            'multiplier_factor' => 1,
            'status' => Meter::STATUS_ACTIVE,
            'is_shared' => false,
            'supports_remote_reading' => false,
            'maintenance_required' => false,
        ];
    }
}
