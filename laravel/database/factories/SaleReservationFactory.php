<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\SaleReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleReservationFactory extends Factory
{
    protected $model = SaleReservation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'reservation_number' => 'RES-'.strtoupper(Str::random(8)),
            'apartment_id' => Apartment::factory()->state([
                'listing_type' => Apartment::LISTING_TYPE_SALE,
                'inventory_status' => Apartment::STATUS_RESERVED,
            ]),
            'buyer_id' => Buyer::factory(),
            'deposit_amount' => 5000,
            'reserved_price' => 120000,
            'currency' => 'USD',
            'expiry_date' => now()->addDays(7)->toDateString(),
            'status' => SaleReservation::STATUS_PENDING_DEPOSIT,
        ];
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => SaleReservation::STATUS_CONFIRMED,
            'deposit_paid_at' => now(),
        ]);
    }
}
