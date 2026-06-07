<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Services\Billing\InvoiceNumberService;
use Exception;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    /**
     * Generates a monthly invoice for an apartment from its active rental agreement.
     *
     * @throws Exception
     */
    public function generateForApartment(Apartment $apartment, int $year, int $month): MonthlyInvoice
    {
        return DB::transaction(function () use ($apartment, $year, $month) {
            $apartment->loadMissing(['building', 'activeLease.rentalAgreement']);

            $agreement = $apartment->activeLease;
            if (! $agreement) {
                throw new Exception('No active rental agreement for this unit.');
            }

            $existing = MonthlyInvoice::query()
                ->where('company_id', $apartment->company_id)
                ->where('apartment_id', $apartment->id)
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->where('contract_type', 'rental')
                ->where('contract_id', $agreement->id)
                ->first();

            if ($existing) {
                throw new Exception('An invoice already exists for this unit and billing period.');
            }

            $rent = $this->calculateRent($apartment, $agreement);
            $services = $this->calculateServices($apartment);
            $utilities = $this->calculateUtilities($apartment, $year, $month);
            $installments = $this->calculateInstallments($apartment);

            $invoice = MonthlyInvoice::create([
                'company_id' => $apartment->company_id,
                'apartment_id' => $apartment->id,
                'contract_id' => $agreement->id,
                'contract_type' => 'rental',
                'invoice_number' => app(InvoiceNumberService::class)->next($apartment->company_id, $year),
                'billing_year' => $year,
                'billing_month' => $month,
                'subtotal_rent' => $rent,
                'subtotal_utilities' => $utilities,
                'subtotal_services' => $services,
                'subtotal_installment' => $installments,
                'discount_amount' => 0,
                'paid_amount' => 0,
                'issue_date' => now()->toDateString(),
                'due_date' => $this->resolveDueDate($agreement),
                'status' => 'draft',
            ]);

            $this->createLineItems($invoice, [
                'rent' => $rent,
                'utilities' => $utilities,
                'services' => $services,
                'installments' => $installments,
            ]);

            return $invoice;
        });
    }

    private function resolveDueDate(Agreement $agreement): string
    {
        $dueDay = (int) ($agreement->rentalAgreement?->payment_due_day ?? 0);
        if ($dueDay >= 1 && $dueDay <= 28) {
            return now()->day($dueDay)->toDateString();
        }

        return now()->addDays(14)->toDateString();
    }

    private function createLineItems(MonthlyInvoice $invoice, array $data): void
    {
        $map = [
            'rent' => ['line_type' => 'rent', 'description' => 'Monthly rental fee'],
            'services' => ['line_type' => 'service', 'description' => 'Building service charge'],
            'utilities' => ['line_type' => 'utility', 'description' => 'Utility consumption'],
            'installments' => ['line_type' => 'installment', 'description' => 'Payment plan installment'],
        ];

        $sort = 0;
        foreach ($data as $key => $amount) {
            if ($amount <= 0) {
                continue;
            }

            InvoiceLineItem::create([
                'monthly_invoice_id' => $invoice->id,
                'line_type' => $map[$key]['line_type'],
                'description' => $map[$key]['description'],
                'quantity' => 1,
                'unit_price' => $amount,
                'amount' => $amount,
                'sort_order' => $sort++,
            ]);
        }
    }

    private function calculateRent(Apartment $apartment, Agreement $agreement): float
    {
        $monthlyRent = $agreement->rentalAgreement?->monthly_rent;
        if ($monthlyRent !== null && (float) $monthlyRent > 0) {
            return (float) $monthlyRent;
        }

        if ($apartment->market_rent_price !== null && (float) $apartment->market_rent_price > 0) {
            return (float) $apartment->market_rent_price;
        }

        return 0.0;
    }

    private function calculateServices(Apartment $apartment): float
    {
        return 0.0;
    }

    private function calculateUtilities(Apartment $apartment, int $year, int $month): float
    {
        return 0.0;
    }

    private function calculateInstallments(Apartment $apartment): float
    {
        return 0.0;
    }
}
