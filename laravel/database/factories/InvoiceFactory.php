<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(), 
        'invoice_number' => $this->faker->unique()->numerify('INV-#####'),
            'invoice_type' => 'rental',
            'status' => 'draft',
            'issue_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => User::factory(),
        ];
    }
}