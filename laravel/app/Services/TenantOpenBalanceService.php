<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Charge;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Carbon\Carbon;

class TenantOpenBalanceService
{
    /**
     * @return array<string, mixed>
     */
    public function forTenant(
        User $user,
        string $tenantId,
        ?string $buildingId = null,
        ?int $year = null,
        ?int $month = null,
    ): array {
        $agreementQuery = Agreement::query()
            ->where('company_id', $user->company_id)
            ->where('tenant_id', $tenantId)
            ->where('status', Agreement::STATUS_ACTIVE);

        if ($buildingId) {
            $agreementQuery->whereHas('apartment', fn ($q) => $q->where('building_id', $buildingId));
        }

        $agreementIds = $agreementQuery->pluck('id');

        $invoiceQuery = MonthlyInvoice::query()
            ->where('company_id', $user->company_id)
            ->where('contract_type', 'rental')
            ->whereIn('contract_id', $agreementIds)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid', 'overdue'])
            ->whereRaw('balance_due > 0')
            ->with(['apartment.building'])
            ->orderBy('issue_date')
            ->orderBy('created_at');

        if ($year) {
            $invoiceQuery->where('billing_year', $year);
        }

        if ($month) {
            $invoiceQuery->where('billing_month', $month);
        }

        $invoices = $invoiceQuery->get();

        $lines = $invoices->map(fn (MonthlyInvoice $invoice) => $this->mapInvoiceLine($invoice))->values()->all();

        $openBalance = round($invoices->sum(fn (MonthlyInvoice $i) => (float) $i->balance_due), 2);

        $pendingUtilities = $this->pendingUtilityCharges(
            $user->company_id,
            $agreementIds,
            $year,
            $month,
        );

        $pendingUtilityTotal = round(
            collect($pendingUtilities)->sum(fn (array $row) => (float) $row['amount']),
            2
        );

        $draftUtilityInvoices = $this->draftInvoicesWithUtilities(
            $user->company_id,
            $agreementIds,
            $year,
            $month,
        );

        return [
            'tenant_id' => $tenantId,
            'building_id' => $buildingId,
            'open_balance' => $openBalance,
            'invoice_count' => count($lines),
            'invoices' => $lines,
            'amounts' => [
                'rent_on_invoices' => round($invoices->sum(fn (MonthlyInvoice $i) => (float) $i->subtotal_rent), 2),
                'utilities_on_invoices' => round($invoices->sum(fn (MonthlyInvoice $i) => (float) $i->subtotal_utilities), 2),
                'services_on_invoices' => round($invoices->sum(fn (MonthlyInvoice $i) => (float) $i->subtotal_services), 2),
                'pending_utilities' => $pendingUtilityTotal,
            ],
            'pending_utilities' => $pendingUtilities,
            'draft_utility_invoices' => $draftUtilityInvoices,
            'total_due_including_pending' => round($openBalance + $pendingUtilityTotal, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapInvoiceLine(MonthlyInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'billing_year' => $invoice->billing_year,
            'billing_month' => $invoice->billing_month,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'subtotal_rent' => (float) $invoice->subtotal_rent,
            'subtotal_utilities' => (float) $invoice->subtotal_utilities,
            'subtotal_services' => (float) $invoice->subtotal_services,
            'total_amount' => (float) $invoice->total_amount,
            'paid_amount' => (float) $invoice->paid_amount,
            'balance_due' => (float) $invoice->balance_due,
            'status' => $invoice->status,
            'unit_number' => $invoice->apartment?->unit_number,
            'building_name' => $invoice->apartment?->building?->name,
        ];
    }

    /**
     * Approved utility charges not yet on a monthly invoice.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $agreementIds
     * @return list<array<string, mixed>>
     */
    protected function pendingUtilityCharges(
        string $companyId,
        $agreementIds,
        ?int $year,
        ?int $month,
    ): array {
        if ($agreementIds->isEmpty()) {
            return [];
        }

        $query = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY)
            ->where('status', Charge::STATUS_APPROVED)
            ->whereNull('invoice_id')
            ->whereIn('rental_agreement_id', $agreementIds)
            ->orderBy('charged_at');

        if ($year && $month) {
            $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();

            $query->where(function ($q) use ($periodStart, $periodEnd) {
                $q->where(function ($period) use ($periodStart, $periodEnd) {
                    $period->whereBetween('service_period_start', [$periodStart, $periodEnd])
                        ->orWhereBetween('service_period_end', [$periodStart, $periodEnd]);
                })->orWhere(function ($fallback) use ($periodStart, $periodEnd) {
                    $fallback->whereNull('service_period_start')
                        ->whereBetween('charged_at', [$periodStart, $periodEnd]);
                });
            });
        }

        return $query->get()->map(fn (Charge $charge) => [
            'id' => $charge->id,
            'charge_number' => $charge->charge_number,
            'description' => $charge->description,
            'amount' => (float) $charge->total_amount,
            'rental_agreement_id' => $charge->rental_agreement_id,
            'charged_at' => optional($charge->charged_at)->format('Y-m-d'),
        ])->values()->all();
    }

    /**
     * Draft monthly invoices that include utilities but are not yet issuable for payment.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $agreementIds
     * @return list<array<string, mixed>>
     */
    protected function draftInvoicesWithUtilities(
        string $companyId,
        $agreementIds,
        ?int $year,
        ?int $month,
    ): array {
        if ($agreementIds->isEmpty()) {
            return [];
        }

        $query = MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('contract_type', 'rental')
            ->whereIn('contract_id', $agreementIds)
            ->where('status', 'draft')
            ->where('subtotal_utilities', '>', 0)
            ->with(['apartment.building'])
            ->orderBy('issue_date');

        if ($year) {
            $query->where('billing_year', $year);
        }

        if ($month) {
            $query->where('billing_month', $month);
        }

        return $query->get()->map(fn (MonthlyInvoice $invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'subtotal_utilities' => (float) $invoice->subtotal_utilities,
            'total_amount' => (float) $invoice->total_amount,
            'balance_due' => (float) $invoice->balance_due,
            'unit_number' => $invoice->apartment?->unit_number,
        ])->values()->all();
    }
}
