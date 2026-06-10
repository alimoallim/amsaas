<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualInvoiceService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(User $actor, array $validated): MonthlyInvoice
    {
        return DB::transaction(function () use ($actor, $validated) {
            $apartment = Apartment::query()
                ->where('company_id', $actor->company_id)
                ->with(['building', 'activeLease.rentalAgreement'])
                ->findOrFail($validated['apartment_id']);

            $agreement = $apartment->activeLease;
            if (! $agreement || $agreement->status !== Agreement::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'apartment_id' => ['This unit has no active rental agreement.'],
                ]);
            }

            $year = (int) $validated['billing_year'];
            $month = (int) $validated['billing_month'];

            $duplicate = MonthlyInvoice::query()
                ->where('company_id', $actor->company_id)
                ->where('apartment_id', $apartment->id)
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->where('contract_type', 'rental')
                ->where('contract_id', $agreement->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'billing_month' => ['An invoice already exists for this unit and billing period.'],
                ]);
            }

            $lineItems = $validated['line_items'] ?? [];
            if ($lineItems === []) {
                throw ValidationException::withMessages([
                    'line_items' => ['Add at least one line item for a manual invoice.'],
                ]);
            }

            $totals = $this->summarizeLineItems($lineItems);
            $issueDate = isset($validated['issue_date'])
                ? Carbon::parse($validated['issue_date'])->toDateString()
                : now()->toDateString();
            $dueDate = isset($validated['due_date'])
                ? Carbon::parse($validated['due_date'])->toDateString()
                : $this->defaultDueDate($agreement);

            if (Carbon::parse($dueDate)->lt(Carbon::parse($issueDate))) {
                throw ValidationException::withMessages([
                    'due_date' => ['Due date must be on or after the issue date.'],
                ]);
            }

            $discount = Money::toScale((string) ($validated['discount_amount'] ?? 0), 2);

            $invoice = MonthlyInvoice::create([
                'company_id' => $actor->company_id,
                'apartment_id' => $apartment->id,
                'invoice_number' => app(InvoiceNumberService::class)->next($actor->company_id, $year),
                'contract_type' => 'rental',
                'contract_id' => $agreement->id,
                'billing_year' => $year,
                'billing_month' => $month,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal_rent' => Money::toScale($totals['rent'], 2),
                'subtotal_utilities' => Money::toScale($totals['utilities'], 2),
                'subtotal_services' => Money::toScale($totals['services'], 2),
                'subtotal_installment' => Money::toScale($totals['installment'], 2),
                'discount_amount' => $discount,
                'paid_amount' => '0.00',
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'generated_by' => $actor->id,
            ]);

            foreach ($lineItems as $index => $row) {
                $quantity = Money::toScale((string) $row['quantity'], 3);
                $unitPrice = Money::toScale((string) $row['unit_price'], 4);
                $amount = Money::toScale(Money::mul($quantity, $unitPrice), 2);

                InvoiceLineItem::create([
                    'monthly_invoice_id' => $invoice->id,
                    'charge_type_id' => $row['charge_type_id'] ?? null,
                    'line_type' => $row['line_type'],
                    'description' => $row['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'sort_order' => $index,
                ]);
            }

            return $invoice->fresh(['lineItems', 'apartment.building']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     * @return array{rent: string, utilities: string, services: string, installment: string}
     */
    protected function summarizeLineItems(array $lineItems): array
    {
        $rent = Money::zero();
        $utilities = Money::zero();
        $services = Money::zero();
        $installment = Money::zero();

        foreach ($lineItems as $row) {
            $quantity = Money::toScale((string) $row['quantity'], 3);
            $unitPrice = Money::toScale((string) $row['unit_price'], 4);
            $amount = Money::mul($quantity, $unitPrice);

            match ($this->bucketForLineType($row['line_type'])) {
                'rent' => $rent = Money::add($rent, $amount),
                'utilities' => $utilities = Money::add($utilities, $amount),
                'installment' => $installment = Money::add($installment, $amount),
                default => $services = Money::add($services, $amount),
            };
        }

        return [
            'rent' => $rent,
            'utilities' => $utilities,
            'services' => $services,
            'installment' => $installment,
        ];
    }

    protected function bucketForLineType(string $lineType): string
    {
        return match ($lineType) {
            'rent' => 'rent',
            'utility', 'electricity', 'water', 'gas' => 'utilities',
            'installment' => 'installment',
            default => 'services',
        };
    }

    protected function defaultDueDate(Agreement $agreement): string
    {
        $rental = RentalAgreement::query()->find($agreement->id);
        $dueDay = (int) ($rental?->payment_due_day ?? 0);

        if ($dueDay >= 1 && $dueDay <= 28) {
            return now()->day($dueDay)->toDateString();
        }

        return now()->addDays(15)->toDateString();
    }
}
