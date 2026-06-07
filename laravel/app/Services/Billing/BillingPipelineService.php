<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\BillingItem;
use App\Models\BillingRun;
use App\Models\Charge;
use App\Models\MeterReading;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\User;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the standard property-management billing cycle:
 * recurring charges → utility charge approval → consolidation → issue → payment.
 */
class BillingPipelineService
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Snapshot of work-in-progress across pipeline stages for a billing period.
     *
     * @return array<string, mixed>
     */
    public function status(?Carbon $billingDate = null): array
    {
        $billingDate = ($billingDate ?? now())->copy()->startOfMonth();
        $companyId = $this->user->company_id;

        $pendingReadings = MeterReading::query()
            ->where('company_id', $companyId)
            ->whereIn('status', [MeterReading::STATUS_DRAFT, MeterReading::STATUS_VERIFIED])
            ->count();

        $pendingUtilityCharges = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY)
            ->where('status', Charge::STATUS_PENDING)
            ->whereNull('invoice_id')
            ->count();

        $approvedUtilityCharges = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY)
            ->where('status', Charge::STATUS_APPROVED)
            ->whereNull('invoice_id')
            ->count();

        $pendingBillingItems = BillingItem::query()
            ->where('company_id', $companyId)
            ->where('posted_to_invoice', false)
            ->count();

        $draftInvoices = MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->where('billing_year', $billingDate->year)
            ->where('billing_month', $billingDate->month)
            ->where('status', 'draft')
            ->count();

        $issuedUnpaid = MonthlyInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['issued', 'finalized', 'partially_paid'])
            ->whereRaw('balance_due > 0')
            ->count();

        return [
            'period' => $billingDate->format('Y-m'),
            'stages' => [
                'meter_readings' => [
                    'label' => 'Usage capture',
                    'pending_approval' => $pendingReadings,
                ],
                'utility_charges' => [
                    'label' => 'Utility charges',
                    'pending_approval' => $pendingUtilityCharges,
                    'ready_to_invoice' => $approvedUtilityCharges,
                ],
                'recurring_charges' => [
                    'label' => 'Recurring billing items',
                    'ready_to_invoice' => $pendingBillingItems,
                ],
                'invoices' => [
                    'label' => 'Monthly invoices',
                    'draft' => $draftInvoices,
                    'issued_unpaid' => $issuedUnpaid,
                ],
            ],
            'can_consolidate' => $pendingBillingItems > 0 || $approvedUtilityCharges > 0,
            'blocking_pending_utility_charges' => $pendingUtilityCharges,
        ];
    }

    /**
     * Run recurring billing (rent/fees) then consolidate draft monthly invoices.
     *
     * @return array<string, mixed>
     */
    public function runMonthlyClose(Carbon $billingDate, bool $generateRecurring = true): array
    {
        $billingDate = $billingDate->copy()->startOfMonth();
        $result = [
            'period' => $billingDate->format('Y-m'),
            'billing_run' => null,
            'consolidation' => [
                'success' => 0,
                'appended' => 0,
                'skipped' => 0,
                'skipped_no_items' => 0,
                'skipped_already_exists' => 0,
                'failed' => 0,
                'errors' => [],
            ],
        ];

        app(BillingCloseReadinessService::class, ['user' => $this->user])
            ->prepareActiveAgreementsForBilling();

        if ($generateRecurring) {
            $run = app(BillingProcessorService::class, [
                'user' => $this->user,
                'billingDate' => $billingDate,
            ])->process();

            $result['billing_run'] = [
                'id' => $run->id,
                'status' => $run->status,
                'run_number' => $run->run_number,
            ];
        }

        $consolidation = app(InvoiceConsolidationService::class, ['currentUser' => $this->user]);
        $rentalAgreements = RentalAgreement::query()
            ->whereHas('agreement', fn ($q) => $q
                ->where('company_id', $this->user->company_id)
                ->where('status', Agreement::STATUS_ACTIVE))
            ->with(['agreement.apartment'])
            ->get();

        foreach ($rentalAgreements as $rentalAgreement) {
            try {
                $outcome = $consolidation->consolidate($rentalAgreement, $billingDate);

                if ($outcome->wasCreated()) {
                    $result['consolidation']['success']++;
                } elseif ($outcome->wasAppended()) {
                    $result['consolidation']['appended']++;
                    $result['consolidation']['success']++;
                } elseif ($outcome->outcome === ConsolidationResult::OUTCOME_SKIPPED_ALREADY_EXISTS) {
                    $result['consolidation']['skipped_already_exists']++;
                    $result['consolidation']['skipped']++;
                } else {
                    $result['consolidation']['skipped_no_items']++;
                    $result['consolidation']['skipped']++;
                }
            } catch (\Throwable $e) {
                $result['consolidation']['failed']++;
                $result['consolidation']['errors'][] = [
                    'agreement_id' => $rentalAgreement->id,
                    'agreement_number' => $rentalAgreement->agreement?->agreement_number,
                    'message' => $e->getMessage(),
                ];
                Log::error('Invoice consolidation failed for agreement.', [
                    'agreement_id' => $rentalAgreement->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $result['pipeline'] = $this->status($billingDate);
        $result['consolidation']['draft_invoices_for_period'] =
            $result['pipeline']['stages']['invoices']['draft'] ?? 0;

        return $result;
    }

    public function issueInvoice(MonthlyInvoice $invoice): MonthlyInvoice
    {
        abort_unless($invoice->company_id === $this->user->company_id, 403);

        app(InvoiceService::class)->issue($invoice);

        return $invoice->fresh();
    }
}
