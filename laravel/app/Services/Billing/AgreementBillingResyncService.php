<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * After agreement charge lines change, generate missing billing items and
 * append them to open draft invoices for the affected periods.
 */
class AgreementBillingResyncService
{
    public function resyncAfterAgreementChange(User $actor, RentalAgreement $rental): void
    {
        $rental->loadMissing('agreement');
        $agreement = $rental->agreement;

        if (! $agreement || $agreement->status !== Agreement::STATUS_ACTIVE) {
            return;
        }

        $periods = $this->resyncPeriods($actor, $agreement);

        foreach ($periods as $billingDate) {
            $created = app(BillingProcessorService::class, [
                'user' => $actor,
                'billingDate' => $billingDate,
            ])->ensureBillingItemsForAgreement($agreement->id);

            $outcome = app(InvoiceConsolidationService::class, [
                'currentUser' => $actor,
            ])->consolidate($rental, $billingDate);

            if ($created > 0 || $outcome->wasAppended() || $outcome->wasCreated()) {
                Log::info('Agreement billing resync updated invoices.', [
                    'agreement_id' => $agreement->id,
                    'period' => $billingDate->format('Y-m'),
                    'billing_items_created' => $created,
                    'consolidation_outcome' => $outcome->outcome,
                ]);
            }
        }
    }

    /**
     * @return Collection<int, Carbon>
     */
    protected function resyncPeriods(User $actor, Agreement $agreement): Collection
    {
        $periods = collect([now()->copy()->startOfMonth()]);

        $draftMonths = MonthlyInvoice::query()
            ->where('company_id', $actor->company_id)
            ->where('contract_type', 'rental')
            ->where('contract_id', $agreement->id)
            ->where('status', 'draft')
            ->get(['billing_year', 'billing_month']);

        foreach ($draftMonths as $invoice) {
            $periods->push(
                Carbon::create((int) $invoice->billing_year, (int) $invoice->billing_month, 1)
                    ->startOfMonth()
            );
        }

        return $periods
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->values();
    }
}
