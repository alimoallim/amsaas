<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MonthlyInvoiceFactory extends Factory
{
    protected $model = MonthlyInvoice::class;

    public function definition(): array
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        return [
            'company_id' => Company::factory(),
            'apartment_id' => Apartment::factory(),
            'invoice_number' => 'INV-'.strtoupper(Str::random(10)),
            'contract_type' => 'rental',
            'contract_id' => (string) Str::uuid(),
            'billing_year' => $year,
            'billing_month' => $month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 0,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ];
    }
}
