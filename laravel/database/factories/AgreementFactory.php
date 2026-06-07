<?php

namespace Database\Factories;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Company;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgreementFactory extends Factory
{
    protected $model = Agreement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'agreement_number' => strtoupper(fake()->unique()->bothify('AGR-####')),
            'agreement_type' => Agreement::TYPE_RENTAL,
            'apartment_id' => Apartment::factory(),
            'tenant_id' => Tenant::factory(),
            'status' => Agreement::STATUS_DRAFT,
            'start_date' => now()->startOfMonth()->toDateString(),
            'currency' => 'USD',
        ];
    }

    public function withRentalAgreement(): static
    {
        return $this->afterCreating(function (Agreement $agreement) {
            RentalAgreement::query()->create([
                'id' => $agreement->id,
                'monthly_rent' => 1000,
                'security_deposit' => 1000,
                'payment_due_day' => 1,
                'billing_cycle' => 'monthly',
            ]);
        });
    }
}
