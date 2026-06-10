<?php

namespace App\Services\Billing;

use Carbon\Carbon;
use App\Models\User;
use App\Models\AgreementCharge;
use App\Models\BillingItem;
use App\Models\BillingRun;
use App\Models\ChargeModel;
use App\Models\RentalAgreement;
use App\Services\Billing\CalculateChargeService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingProcessorService
{
    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    protected BillingRun $billingRun;
    protected User $user;
    protected Carbon $billingDate;
    protected CalculateChargeService $calculationService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Dependencies are injected cleanly via the service container.
    |--------------------------------------------------------------------------
    */

    public function __construct(
        User $user,
        CalculateChargeService $calculationService,
        ?Carbon $billingDate = null
    ) {
        $this->user = $user;
        $this->calculationService = $calculationService;
        // Enforce an immutable instance snapshot of the processing execution timeline
        $this->billingDate = $billingDate ? $billingDate->copy() : now();
    }
    

    /*
    |--------------------------------------------------------------------------
    | Execute Bulk Billing Run Cycle
    |--------------------------------------------------------------------------
    */

    public function process(): BillingRun
    {
        // Create the batch management record independently first to track real-time processing state
        $this->billingRun = $this->createBillingRun();

        BillingRun::where('status', 'running')
        ->where('execution_started_at', '<', now()->subHours(2))
        ->update([
            'status' => 'failed', 
            'error_summary' => 'Terminated by system timeout (pre-flight cleanup).'
        ]);
    // ------------------------

    

        try {
            $this->billingRun->update([
                'status' => BillingRun::STATUS_RUNNING,
                'execution_started_at' => now(),
            ]);

            // Pull matching processing configurations up-front using optimal eager loading
            $charges = $this->getBillableCharges();

            foreach ($charges as $charge) {
                $this->processSingleChargeWithIsolation($charge);
            }

            $this->finalizeRun();

            return $this->billingRun;
        }
        catch (Throwable $exception) {
            Log::critical('Fatal breakdown inside billing batch processor loop.', [
                'company_id' => $this->user->company_id,
                'billing_run_id' => $this->billingRun->id ?? 'N/A',
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if (isset($this->billingRun)) {
                $this->billingRun->update([
                    'status' => BillingRun::STATUS_FAILED,
                    'execution_completed_at' => now(),
                    'error_summary' => 'Fatal Execution Failure: ' . $exception->getMessage(),
                ]);
            }

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Isolated Processing Boundary
    |--------------------------------------------------------------------------
    | Wraps single items in independent transactions to prevent cascading batch collapses.
    |--------------------------------------------------------------------------
    */

    protected function buildCalculationContext(AgreementCharge $charge): array
    {
        $rental = RentalAgreement::find($charge->agreement_id);
        $overrideAmount = $charge->override_amount;

        return [
            'monthly_rent' => $rental?->monthly_rent,
            'override_amount' => $overrideAmount !== null ? (float) $overrideAmount : null,
            'flat_amount' => $overrideAmount !== null ? (float) $overrideAmount : null,
            'base_amount' => $overrideAmount !== null ? (float) $overrideAmount : null,
            'consumption' => (float) $charge->quantity,
        ];
    }

    protected function processSingleChargeWithIsolation(AgreementCharge $charge): void
    {
        // Pre-flight check before initiating a database transaction to optimize resources
        if ($this->billingItemExists($charge)) {
            Log::warning('Idempotency block triggered; charge bypassed.', [
                'agreement_charge_id' => $charge->id,
                'billing_period_start' => $this->billingDate->copy()->startOfMonth()->toDateString()
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            $this->processAgreementCharge($charge);
            DB::commit();
        }
        catch (Throwable $exception) {
            DB::rollBack();

            $this->incrementFailureMetrics();

            Log::error('Individual agreement charge processing aborted.', [
                'agreement_charge_id' => $charge->id,
                'billing_run_id' => $this->billingRun->id,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Single Agreement Charge
    |--------------------------------------------------------------------------
    */

    protected function processAgreementCharge(AgreementCharge $charge): void
    {
        $periodStart = $this->billingDate->copy()->startOfMonth();
        $periodEnd = $this->billingDate->copy()->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Build Context Array & Delegate Calculations
        |--------------------------------------------------------------------------
        | Resolves dynamic fields by sending explicit state contexts down to your
        | strategy calculation engine layer.
        |--------------------------------------------------------------------------
        */
        $context = $this->buildCalculationContext($charge);

        $result = $this->calculationService->calculate($charge->chargeModel, $context);

        /*
        |--------------------------------------------------------------------------
        | Create Pre-Invoice Billing Register Line Item
        |--------------------------------------------------------------------------
        | Populates financial parameters accurately using your verified DTO properties.
        |--------------------------------------------------------------------------
        */
        BillingItem::create([
            'id' => (string) Str::uuid(),
            'company_id' => $charge->company_id,
            'agreement_id' => $charge->agreement_id,
            'agreement_charge_id' => $charge->id,
            'charge_model_id' => $charge->charge_model_id,
            'charge_type_id' => $charge->charge_type_id,
            'billing_run_id' => $this->billingRun->id,
            
            'billing_period_start' => $periodStart->toDateString(),
            'billing_period_end' => $periodEnd->toDateString(),
            'billing_date' => $this->billingDate->toDateString(),
            'due_date' => $this->billingDate->copy()->addDays(7)->toDateString(),

            'quantity' => $charge->quantity,
            // Revert back to the override unit rate before selecting model constraints
            'unit_rate' => $charge->override_unit_rate ?? $charge->chargeModel->unit_rate ?? 0.00,
            
            'base_amount' => $result->amount,
            'tax_amount' => $result->taxAmount,
            'discount_amount' => 0.00, 
            'penalty_amount' => 0.00,
            'adjustment_amount' => 0.00,
            'subtotal_amount' => $result->subtotal,
            'total_amount' => $result->totalAmount,
            
            'currency' => $charge->chargeModel->currency ?? 'USD',
            'description' => $this->buildBillingDescription($charge),
            'generated_at' => now(),
            'status' => 'pending', // Aligning to migration string baseline definitions
            
            'posted_to_invoice' => false,
            'posted_to_ledger' => false,
            
            'calculation_snapshot' => $result->breakdown,
            'metadata' => [
                'engine_version' => '2.0.0',
                'processed_by_run_number' => $this->billingRun->run_number
            ],
            'created_by' => $this->user->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mutate Scheduling Matrix Parameters
        |--------------------------------------------------------------------------
        | Moves the scheduling execution markers directly on the rule configuration.
        |--------------------------------------------------------------------------
        */
        $currentNextDate = Carbon::parse($charge->next_billing_date ?? $this->billingDate);
        $calculatedNextDate = $this->calculateNextCycleDate(
            $currentNextDate, 
            $charge->chargeModel->billing_frequency ?? 'monthly'
        );

        $charge->update([
            'next_billing_date' => $calculatedNextDate->toDateString()
        ]);

        $this->incrementSuccessMetrics($result->totalAmount);
    }

    /*
    |--------------------------------------------------------------------------
    | Core Helpers
    |--------------------------------------------------------------------------
    */

    protected function createBillingRun(): BillingRun
    {
        return BillingRun::create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->user->company_id,
            'run_number' => $this->generateRunNumber(),
            'name' => 'Monthly Automated Billing Run',
            'description' => 'Asynchronous contract execution cycle for ' . $this->billingDate->format('F Y'),
            'billing_frequency' => 'monthly',
            'billing_period_start' => $this->billingDate->copy()->startOfMonth()->toDateString(),
            'billing_period_end' => $this->billingDate->copy()->endOfMonth()->toDateString(),
            'scheduled_at' => now(),
            'currency' => 'USD',
            'status' => 'pending',
            'created_by' => $this->user->id,
            'executed_by' => $this->user->id,
        ]);
    }

    /**
     * Generate pending billing items for one agreement's recurring charges in the
     * processor billing period (idempotent per charge + period).
     */
    public function ensureBillingItemsForAgreement(string $agreementId): int
    {
        $this->billingRun = $this->createBillingRun();
        $this->billingRun->update([
            'status' => BillingRun::STATUS_RUNNING,
            'execution_started_at' => now(),
            'name' => 'Agreement charge resync',
            'description' => 'Billing items after agreement charge update for '
                .$this->billingDate->format('F Y'),
        ]);

        $created = 0;

        foreach ($this->getBillableCharges($agreementId) as $charge) {
            if ($this->billingItemExists($charge)) {
                continue;
            }

            $this->processSingleChargeWithIsolation($charge);
            $created++;
        }

        $this->billingRun->update([
            'status' => 'completed',
            'execution_completed_at' => now(),
        ]);

        return $created;
    }

    protected function getBillableCharges(?string $agreementId = null)
    {
        return AgreementCharge::query()
            ->with(['agreement', 'chargeModel', 'chargeType'])
            ->where('company_id', $this->user->company_id)
            ->when($agreementId, fn ($query) => $query->where('agreement_id', $agreementId))
            ->where('status', 'active')
            ->where('is_suspended', false)
            ->whereHas(
                'chargeModel',
                fn ($query) => $query->whereIn('pricing_strategy', [
                    ChargeModel::STRATEGY_AGREEMENT_RENT,
                    ChargeModel::STRATEGY_FLAT_FEE,
                    ChargeModel::STRATEGY_FIXED,
                ])
            )
            ->whereDate('billing_start_date', '<=', $this->billingDate->toDateString())
            ->where(function ($query) {
                $query->whereNull('billing_end_date')
                      ->orWhereDate('billing_end_date', '>=', $this->billingDate->toDateString());
            })
            ->orderBy('priority', 'asc')
            ->get();
    }

    protected function billingItemExists(AgreementCharge $charge): bool
    {
        return BillingItem::query()
            ->where('agreement_charge_id', $charge->id)
            ->whereDate('billing_period_start', $this->billingDate->copy()->startOfMonth()->toDateString())
            ->whereDate('billing_period_end', $this->billingDate->copy()->endOfMonth()->toDateString())
            ->exists();
    }

    protected function calculateNextCycleDate(Carbon $currentDate, string $frequency): Carbon
    {
        return match (strtolower($frequency)) {
            'monthly'     => $currentDate->copy()->addMonth(),
            'quarterly'   => $currentDate->copy()->addMonths(3),
            'semi_annual' => $currentDate->copy()->addMonths(6),
            'annual'      => $currentDate->copy()->addYear(),
            default       => $currentDate->copy()->addMonth(),
        };
    }

    protected function buildBillingDescription(AgreementCharge $charge): string
    {
        return sprintf(
            '%s - Recurring Charge for %s',
            $charge->custom_name ?? $charge->chargeType->name,
            $this->billingDate->format('F Y')
        );
    }

    protected function generateRunNumber(): string
    {
        $datePrefix = now()->format('Ym');
        
        $count = BillingRun::query()
            ->where('company_id', $this->user->company_id)
            ->count();

        return sprintf('BR-%s-%05d', $datePrefix, $count + 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Telemetry Metrics
    |--------------------------------------------------------------------------
    */

    protected function incrementSuccessMetrics(float $amount): void
    {
        $this->billingRun->increment('total_billing_items_generated');
        $this->billingRun->increment('total_successful_items');
        $this->billingRun->increment('success_count');
        $this->billingRun->increment('total_amount', $amount);
    }

    protected function incrementFailureMetrics(): void
    {
        $this->billingRun->increment('total_failed_items');
        $this->billingRun->increment('failure_count');
    }

    protected function finalizeRun(): void
    {
        // If some items collapsed but others succeeded, flag the batch as partially completed
        $finalStatus = $this->billingRun->fresh()->failure_count > 0
            ? 'partially_completed'
            : 'completed';

        $this->billingRun->update([
            'status' => $finalStatus,
            'execution_completed_at' => now(),
        ]);
    }
}