<?php

namespace App\Services\Billing;

use App\Models\Charge;
use App\Models\RentalAgreement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ApprovedChargeInvoiceSyncService
{
    /**
     * Attach an approved utility charge to the tenant's monthly invoice for its billing period.
     */
    public function syncAfterApproval(Charge $charge, User $user): ?ConsolidationResult
    {
        $charge = $charge->fresh();

        if ($charge->category !== Charge::CATEGORY_UTILITY) {
            return null;
        }

        if ($charge->status !== Charge::STATUS_APPROVED) {
            return null;
        }

        if ($charge->invoice_id) {
            return null;
        }

        if (! $charge->rental_agreement_id) {
            Log::warning('Approved utility charge has no rental agreement; cannot sync to invoice.', [
                'charge_id' => $charge->id,
            ]);

            return null;
        }

        $rentalAgreement = RentalAgreement::query()
            ->with('agreement')
            ->find($charge->rental_agreement_id);

        if (! $rentalAgreement) {
            Log::warning('Rental agreement missing for approved utility charge.', [
                'charge_id' => $charge->id,
                'rental_agreement_id' => $charge->rental_agreement_id,
            ]);

            return null;
        }

        $billingDate = $this->resolveBillingDate($charge);

        return (new InvoiceConsolidationService($user))->consolidate($rentalAgreement, $billingDate);
    }

    protected function resolveBillingDate(Charge $charge): Carbon
    {
        if ($charge->service_period_end) {
            return Carbon::parse($charge->service_period_end)->startOfMonth();
        }

        if ($charge->service_period_start) {
            return Carbon::parse($charge->service_period_start)->startOfMonth();
        }

        if ($charge->charged_at) {
            return Carbon::parse($charge->charged_at)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
