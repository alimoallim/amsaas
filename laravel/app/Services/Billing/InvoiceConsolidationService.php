<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\BillingItem;
use App\Models\Charge;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class InvoiceConsolidationService
{
    public function __construct(protected User $currentUser) {}

    /**
     * Consolidate pending billing items and utility charges into a monthly invoice.
     */
    public function consolidate(RentalAgreement $rentalAgreement, Carbon $billingDate): ConsolidationResult
    {
        return DB::transaction(function () use ($rentalAgreement, $billingDate) {
            $rentalAgreement->loadMissing('agreement.apartment');
            $agreement = $rentalAgreement->agreement;

            if (! $agreement) {
                return new ConsolidationResult(null, ConsolidationResult::OUTCOME_SKIPPED_NO_ITEMS);
            }

            $billingItems = $this->pendingBillingItems($agreement->id, $billingDate);
            $utilityCharges = $this->pendingUtilityCharges($rentalAgreement->id, $billingDate);

            if ($billingItems->isEmpty() && $utilityCharges->isEmpty()) {
                Log::info('No pending ledger items for consolidation.', [
                    'agreement_id' => $agreement->id,
                    'company_id' => $this->currentUser->company_id,
                ]);

                return new ConsolidationResult(
                    null,
                    ConsolidationResult::OUTCOME_SKIPPED_NO_ITEMS,
                    $agreement->agreement_number,
                );
            }

            $existing = MonthlyInvoice::query()
                ->where('company_id', $this->currentUser->company_id)
                ->where('apartment_id', $agreement->apartment_id)
                ->where('billing_year', $billingDate->year)
                ->where('billing_month', $billingDate->month)
                ->where('contract_type', 'rental')
                ->where('contract_id', $agreement->id)
                ->first();

            if ($existing) {
                if ($existing->status === 'cancelled') {
                    Log::warning('Cannot append charges to a cancelled invoice.', [
                        'monthly_invoice_id' => $existing->id,
                    ]);

                    return new ConsolidationResult(
                        $existing,
                        ConsolidationResult::OUTCOME_SKIPPED_ALREADY_EXISTS,
                        $agreement->agreement_number,
                    );
                }

                return $this->appendToExistingInvoice(
                    $existing,
                    $billingItems,
                    $utilityCharges,
                    $agreement,
                );
            }

            $rentSubtotal = Money::zero();
            $utilitySubtotal = Money::zero();
            $servicesSubtotal = Money::zero();

            foreach ($billingItems as $item) {
                $lineTotal = Money::toScale((string) $item->total_amount, 2);
                $rentSubtotal = Money::add($rentSubtotal, $lineTotal);
            }

            foreach ($utilityCharges as $charge) {
                $lineTotal = Money::toScale((string) $charge->total_amount, 2);
                $utilitySubtotal = Money::add($utilitySubtotal, $lineTotal);
            }

            $invoice = MonthlyInvoice::create([
                'company_id' => $this->currentUser->company_id,
                'apartment_id' => $agreement->apartment_id,
                'invoice_number' => app(InvoiceNumberService::class)->next(
                    $this->currentUser->company_id,
                    $billingDate->year
                ),
                'contract_type' => 'rental',
                'contract_id' => $agreement->id,
                'billing_year' => $billingDate->year,
                'billing_month' => $billingDate->month,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'subtotal_rent' => Money::toScale($rentSubtotal, 2),
                'subtotal_utilities' => Money::toScale($utilitySubtotal, 2),
                'subtotal_services' => Money::toScale($servicesSubtotal, 2),
                'subtotal_installment' => '0.00',
                'discount_amount' => '0.00',
                'paid_amount' => '0.00',
                'status' => 'draft',
                'generated_by' => $this->currentUser->id,
            ]);

            $this->compileLineItemsAndLockSources($invoice, $billingItems, $utilityCharges);

            Log::info('Monthly invoice consolidated.', [
                'monthly_invoice_id' => $invoice->id,
                'agreement_id' => $agreement->id,
            ]);

            return new ConsolidationResult(
                $invoice->fresh(),
                ConsolidationResult::OUTCOME_CREATED,
                $agreement->agreement_number,
            );
        });
    }

    protected function appendToExistingInvoice(
        MonthlyInvoice $invoice,
        Collection $billingItems,
        Collection $utilityCharges,
        Agreement $agreement,
    ): ConsolidationResult {
        $rentAdd = Money::zero();
        $utilityAdd = Money::zero();

        foreach ($billingItems as $item) {
            $rentAdd = Money::add($rentAdd, Money::toScale((string) $item->total_amount, 2));
        }

        foreach ($utilityCharges as $charge) {
            $utilityAdd = Money::add($utilityAdd, Money::toScale((string) $charge->total_amount, 2));
        }

        $invoice->update([
            'subtotal_rent' => Money::toScale(
                Money::add(Money::toScale((string) $invoice->subtotal_rent, 2), $rentAdd),
                2
            ),
            'subtotal_utilities' => Money::toScale(
                Money::add(Money::toScale((string) $invoice->subtotal_utilities, 2), $utilityAdd),
                2
            ),
        ]);

        $sortStart = (int) (InvoiceLineItem::query()
            ->where('monthly_invoice_id', $invoice->id)
            ->max('sort_order') ?? -1) + 1;

        $this->compileLineItemsAndLockSources(
            $invoice->fresh(),
            $billingItems,
            $utilityCharges,
            $sortStart,
        );

        $invoice = $invoice->fresh();
        $this->refreshInvoicePaymentStatus($invoice);

        if ($agreement->tenant_id) {
            app(PaymentService::class)->reapplyUnallocatedPayments(
                $this->currentUser->company_id,
                $agreement->tenant_id,
            );
        }

        Log::info('Pending charges appended to existing monthly invoice.', [
            'monthly_invoice_id' => $invoice->id,
            'agreement_id' => $agreement->id,
            'utility_lines' => $utilityCharges->count(),
            'billing_lines' => $billingItems->count(),
        ]);

        return new ConsolidationResult(
            $invoice->fresh(),
            ConsolidationResult::OUTCOME_APPENDED,
            $agreement->agreement_number,
        );
    }

    protected function pendingBillingItems(string $agreementId, Carbon $billingDate): Collection
    {
        $periodStart = $billingDate->copy()->startOfMonth()->toDateString();
        $periodEnd = $billingDate->copy()->endOfMonth()->toDateString();

        return BillingItem::query()
            ->where('company_id', $this->currentUser->company_id)
            ->where('agreement_id', $agreementId)
            ->where('posted_to_invoice', false)
            ->whereDate('billing_period_start', $periodStart)
            ->whereDate('billing_period_end', $periodEnd)
            ->get();
    }

    protected function pendingUtilityCharges(string $rentalAgreementId, Carbon $billingDate): Collection
    {
        $periodStart = $billingDate->copy()->startOfMonth()->startOfDay();
        $periodEnd = $billingDate->copy()->endOfMonth()->endOfDay();

        return Charge::query()
            ->where('company_id', $this->currentUser->company_id)
            ->where('rental_agreement_id', $rentalAgreementId)
            ->whereNull('invoice_id')
            ->where('status', Charge::STATUS_APPROVED)
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->where(function ($period) use ($periodStart, $periodEnd) {
                    $period->whereBetween('service_period_start', [$periodStart, $periodEnd])
                        ->orWhereBetween('service_period_end', [$periodStart, $periodEnd])
                        ->orWhere(function ($inner) use ($periodStart, $periodEnd) {
                            $inner->whereDate('service_period_start', '<=', $periodStart)
                                ->whereDate('service_period_end', '>=', $periodEnd);
                        });
                })->orWhere(function ($fallback) use ($periodStart, $periodEnd) {
                    $fallback->whereNull('service_period_start')
                        ->whereBetween('charged_at', [$periodStart, $periodEnd]);
                });
            })
            ->get();
    }

    protected function refreshInvoicePaymentStatus(MonthlyInvoice $invoice): void
    {
        if (! in_array($invoice->status, ['paid', 'partially_paid', 'issued', 'finalized', 'overdue'], true)) {
            return;
        }

        $invoice->refresh();

        if ((float) $invoice->balance_due <= 0.009) {
            if ($invoice->status !== 'paid') {
                $invoice->update(['status' => 'paid']);
            }

            return;
        }

        if ((float) $invoice->paid_amount > 0) {
            $status = $invoice->due_date && $invoice->due_date->isPast() ? 'overdue' : 'partially_paid';
            if ($invoice->status !== $status) {
                $invoice->update(['status' => $status]);
            }
        }
    }

    protected function compileLineItemsAndLockSources(
        MonthlyInvoice $invoice,
        Collection $billingItems,
        Collection $utilityCharges,
        int $sortStart = 0,
    ): void {
        $sort = $sortStart;

        foreach ($billingItems as $item) {
            InvoiceLineItem::create([
                'monthly_invoice_id' => $invoice->id,
                'line_type' => 'rent',
                'description' => $item->description ?? 'Recurring contract charge',
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_rate ?? $item->subtotal_amount,
                'amount' => $item->total_amount,
                'reference_type' => BillingItem::class,
                'reference_id' => $item->id,
                'sort_order' => $sort++,
            ]);
        }

        if ($billingItems->isNotEmpty()) {
            BillingItem::whereIn('id', $billingItems->pluck('id'))
                ->update([
                    'invoice_id' => $invoice->id,
                    'posted_to_invoice' => true,
                    'status' => 'posted',
                ]);
        }

        foreach ($utilityCharges as $charge) {
            InvoiceLineItem::create([
                'monthly_invoice_id' => $invoice->id,
                'line_type' => $this->utilityLineType($charge),
                'description' => $charge->description ?? 'Utility consumption charge',
                'quantity' => $charge->quantity ?? 1,
                'unit_price' => $charge->unit_rate ?? $charge->subtotal_amount,
                'amount' => $charge->total_amount,
                'reference_type' => Charge::class,
                'reference_id' => $charge->id,
                'sort_order' => $sort++,
            ]);
        }

        if ($utilityCharges->isNotEmpty()) {
            Charge::whereIn('id', $utilityCharges->pluck('id'))
                ->update([
                    'invoice_id' => $invoice->id,
                    'status' => Charge::STATUS_INVOICED,
                    'invoiced_at' => now(),
                ]);
        }
    }

    protected function utilityLineType(Charge $charge): string
    {
        return match ($charge->category) {
            Charge::CATEGORY_UTILITY => 'electricity',
            default => 'utility',
        };
    }

}
