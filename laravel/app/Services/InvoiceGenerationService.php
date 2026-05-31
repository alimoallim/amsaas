<?php 
namespace App\Services;

use App\Models\{Apartment, MonthlyInvoice, InvoiceLineItem};
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    public function generateForApartment(Apartment $apartment, int $year, int $month): MonthlyInvoice
    {
        return DB::transaction(function () use ($apartment, $year, $month) {
            // 1. Calculate base components (Rental Agreement, Service Fees, Utilities, Installments)
            // Note: You would fetch active agreements/readings here
            $rent = $this->calculateRent($apartment);
            $services = $this->calculateServices($apartment);
            $utilities = $this->calculateUtilities($apartment, $year, $month);
            $installments = $this->calculateInstallments($apartment);

            // 2. Create the master invoice
            $invoice = MonthlyInvoice::create([
                'company_id' => $apartment->building->company_id,
                'apartment_id' => $apartment->id,
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'billing_year' => $year,
                'billing_month' => $month,
                'subtotal_rent' => $rent,
                'subtotal_services' => $services,
                'subtotal_utilities' => $utilities,
                'subtotal_installment' => $installments,
                'issue_date' => now(),
                'due_date' => now()->addDays(7), // Business policy
                'status' => 'draft',
            ]);

            // 3. Create individual line items for transparency
            $this->createLineItems($invoice, $rent, $services, $utilities, $installments);

            return $invoice;
        });
    }

    private function createLineItems(MonthlyInvoice $invoice, ...$totals): void
    {
        // Logic to insert rows into invoice_line_items based on calculated amounts
        // This allows the tenant to see exactly what they are paying for.
    }
}