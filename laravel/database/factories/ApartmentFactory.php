<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'building_id' => Building::factory(),
            'unit_number' => (string) fake()->unique()->numberBetween(100, 999),
            'floor' => fake()->numberBetween(1, 10),
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area_sqm' => 75,
            'property_type' => 'apartment',
            'listing_type' => Apartment::LISTING_TYPE_RENTAL,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ];
    }
}
