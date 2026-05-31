<?php

namespace App\Services\Billing;

use Carbon\Carbon;
use App\Models\User;
use App\Models\AgreementCharge;
use App\Models\BillingItem;
use App\Models\BillingRun;
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

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        User $user,
        ?Carbon $billingDate = null
    ) {

        $this->user =
            $user;

        $this->billingDate =
            $billingDate
            ??
            now();
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Billing Run
    |--------------------------------------------------------------------------
    */

    public function process(): BillingRun
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Create Billing Run
            |--------------------------------------------------------------------------
            */

            $this->billingRun =
                $this->createBillingRun();

            /*
            |--------------------------------------------------------------------------
            | Mark Running
            |--------------------------------------------------------------------------
            */

            $this->billingRun->update([

                'status' =>
                    BillingRun::STATUS_RUNNING,

                'execution_started_at' =>
                    now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Fetch Billable Charges
            |--------------------------------------------------------------------------
            */

            $charges =
                $this->getBillableCharges();

            /*
            |--------------------------------------------------------------------------
            | Process Charges
            |--------------------------------------------------------------------------
            */

            foreach (
                $charges as $charge
            ) {

                $this->processAgreementCharge(
                    $charge
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Finalize Run
            |--------------------------------------------------------------------------
            */

            $this->finalizeRun();

            DB::commit();

            return $this->billingRun;
        }

        catch (Throwable $exception) {

            DB::rollBack();

            Log::error(

                'Billing processor failed.',

                [

                    'message' =>
                        $exception->getMessage(),

                    'trace' =>
                        $exception->getTraceAsString(),
                ]
            );

            if (
                isset(
                    $this->billingRun
                )
            ) {

                $this->billingRun->update([

                    'status' =>
                        BillingRun::STATUS_FAILED,

                    'execution_completed_at' =>
                        now(),

                    'error_summary' =>
                        $exception->getMessage(),
                ]);
            }

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Billing Run
    |--------------------------------------------------------------------------
    */

    protected function createBillingRun(): BillingRun
    {
        return BillingRun::create([

            'id' =>
                (string) Str::uuid(),

            'company_id' =>
                $this->user->company_id,

            'run_number' =>
                $this->generateRunNumber(),

            'name' =>
                'Monthly Billing Run',

            'description' =>
                'Automated ERP billing cycle execution.',

            'billing_frequency' =>
                BillingRun::FREQUENCY_MONTHLY,

            'billing_period_start' =>
                $this->billingDate
                    ->copy()
                    ->startOfMonth(),

            'billing_period_end' =>
                $this->billingDate
                    ->copy()
                    ->endOfMonth(),

            'scheduled_at' =>
                now(),

            'currency' =>
                'USD',

            'status' =>
                BillingRun::STATUS_PENDING,

            'created_by' =>
                $this->user->id,

            'executed_by' =>
                $this->user->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Billable Charges
    |--------------------------------------------------------------------------
    */

    protected function getBillableCharges()
    {
        return AgreementCharge::query()

            ->with([

                'agreement',

                'chargeModel',

                'chargeType',
            ])

            ->where(

                'company_id',

                $this->user->company_id
            )

            ->where(

                'status',

                AgreementCharge::STATUS_ACTIVE
            )

            ->where(

                'is_suspended',

                false
            )

            ->whereDate(

                'billing_start_date',

                '<=',

                $this->billingDate
            )

            ->where(function ($query) {

                $query->whereNull(
                    'billing_end_date'
                )

                ->orWhereDate(

                    'billing_end_date',

                    '>=',

                    $this->billingDate
                );
            })

            ->orderBy(
                'priority'
            )

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Process Single Agreement Charge
    |--------------------------------------------------------------------------
    */

    protected function processAgreementCharge(
        AgreementCharge $charge
    ): void {

        try {

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Billing
            |--------------------------------------------------------------------------
            */

            if (
                $this->billingItemExists(
                    $charge
                )
            ) {

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Determine Billing Period
            |--------------------------------------------------------------------------
            */

            $periodStart =
                $this->billingDate
                    ->copy()
                    ->startOfMonth();

            $periodEnd =
                $this->billingDate
                    ->copy()
                    ->endOfMonth();

            /*
            |--------------------------------------------------------------------------
            | Resolve Pricing
            |--------------------------------------------------------------------------
            */

            $calculation =
                $this->calculateCharge(
                    $charge
                );

            /*
            |--------------------------------------------------------------------------
            | Create Billing Item
            |--------------------------------------------------------------------------
            */

            BillingItem::create([

                'id' =>
                    (string) Str::uuid(),

                'company_id' =>
                    $charge->company_id,

                'agreement_id' =>
                    $charge->agreement_id,

                'agreement_charge_id' =>
                    $charge->id,

                'charge_model_id' =>
                    $charge->charge_model_id,

                'charge_type_id' =>
                    $charge->charge_type_id,

                'billing_run_id' =>
                    $this->billingRun->id,

                'billing_period_start' =>
                    $periodStart,

                'billing_period_end' =>
                    $periodEnd,

                'billing_date' =>
                    $this->billingDate,

                'due_date' =>
                    $this->billingDate
                        ->copy()
                        ->addDays(7),

                'quantity' =>
                    $calculation['quantity'],

                'unit_rate' =>
                    $calculation['unit_rate'],

                'base_amount' =>
                    $calculation['base_amount'],

                'tax_amount' =>
                    $calculation['tax_amount'],

                'discount_amount' =>
                    $calculation['discount_amount'],

                'penalty_amount' =>
                    $calculation['penalty_amount'],

                'adjustment_amount' =>
                    0,

                'subtotal_amount' =>
                    $calculation['subtotal_amount'],

                'total_amount' =>
                    $calculation['total_amount'],

                'currency' =>
                    $charge
                        ->chargeModel
                        ->currency,

                'description' =>
                    $this->buildBillingDescription(
                        $charge
                    ),

                'generated_at' =>
                    now(),

                'status' =>
                    BillingItem::STATUS_PENDING,

                'posted_to_invoice' =>
                    false,

                'posted_to_ledger' =>
                    false,

                'calculation_snapshot' =>

                    $calculation,

                'created_by' =>
                    $this->user->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Metrics
            |--------------------------------------------------------------------------
            */

            $this->incrementSuccessMetrics(
                $calculation['total_amount']
            );
        }

        catch (Throwable $exception) {

            $this->incrementFailureMetrics();

            Log::error(

                'Agreement charge processing failed.',

                [

                    'agreement_charge_id' =>
                        $charge->id,

                    'message' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Charge Calculation Engine
    |--------------------------------------------------------------------------
    */

    protected function calculateCharge(
        AgreementCharge $charge
    ): array {

        $model =
            $charge->chargeModel;

        /*
        |--------------------------------------------------------------------------
        | Resolve Quantity
        |--------------------------------------------------------------------------
        */

        $quantity =
            (float)
            $charge->quantity;

        /*
        |--------------------------------------------------------------------------
        | Resolve Unit Rate
        |--------------------------------------------------------------------------
        */

        $unitRate =
            $charge->override_unit_rate
            ??
            $model->unit_rate
            ??
            $model->base_amount
            ??
            0;

        /*
        |--------------------------------------------------------------------------
        | Base Amount
        |--------------------------------------------------------------------------
        */

        $baseAmount =
            $quantity
            *
            $unitRate;

        /*
        |--------------------------------------------------------------------------
        | Override Amount
        |--------------------------------------------------------------------------
        */

        if (
            $charge->override_amount
        ) {

            $baseAmount =
                $charge->override_amount;
        }

        /*
        |--------------------------------------------------------------------------
        | Tax Calculation
        |--------------------------------------------------------------------------
        */

        $taxAmount = 0;

        if (
            $model->taxable
            &&
            $model->tax_rate
        ) {

            $taxAmount =
                (
                    $baseAmount
                    *
                    $model->tax_rate
                )
                / 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Discount Calculation
        |--------------------------------------------------------------------------
        */

        $discountAmount = 0;

        /*
        |--------------------------------------------------------------------------
        | Penalty Calculation
        |--------------------------------------------------------------------------
        */

        $penaltyAmount = 0;

        /*
        |--------------------------------------------------------------------------
        | Subtotal
        |--------------------------------------------------------------------------
        */

        $subtotal =
            $baseAmount
            +
            $taxAmount;

        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $total =
            $subtotal
            +
            $penaltyAmount
            -
            $discountAmount;

        return [

            'quantity' =>
                round(
                    $quantity,
                    4
                ),

            'unit_rate' =>
                round(
                    $unitRate,
                    6
                ),

            'base_amount' =>
                round(
                    $baseAmount,
                    2
                ),

            'tax_amount' =>
                round(
                    $taxAmount,
                    2
                ),

            'discount_amount' =>
                round(
                    $discountAmount,
                    2
                ),

            'penalty_amount' =>
                round(
                    $penaltyAmount,
                    2
                ),

            'subtotal_amount' =>
                round(
                    $subtotal,
                    2
                ),

            'total_amount' =>
                round(
                    $total,
                    2
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Billing Protection
    |--------------------------------------------------------------------------
    */

    protected function billingItemExists(
        AgreementCharge $charge
    ): bool {

        return BillingItem::query()

            ->where(

                'agreement_charge_id',

                $charge->id
            )

            ->whereDate(

                'billing_period_start',

                $this->billingDate
                    ->copy()
                    ->startOfMonth()
            )

            ->whereDate(

                'billing_period_end',

                $this->billingDate
                    ->copy()
                    ->endOfMonth()
            )

            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Build Description
    |--------------------------------------------------------------------------
    */

    protected function buildBillingDescription(
        AgreementCharge $charge
    ): string {

        return sprintf(

            '%s - %s Billing',

            $charge
                ->chargeType
                ->name,

            $this->billingDate
                ->format('F Y')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */

    protected function incrementSuccessMetrics(
        float $amount
    ): void {

        $this->billingRun->increment(

            'total_billing_items_generated'
        );

        $this->billingRun->increment(
            'total_successful_items'
        );

        $this->billingRun->increment(
            'success_count'
        );

        $this->billingRun->increment(

            'total_amount',

            $amount
        );
    }

    protected function incrementFailureMetrics(): void
    {
        $this->billingRun->increment(
            'total_failed_items'
        );

        $this->billingRun->increment(
            'failure_count'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Finalize Billing Run
    |--------------------------------------------------------------------------
    */

    protected function finalizeRun(): void
    {
        $status =
            $this->billingRun
                ->failure_count > 0

                ? BillingRun::STATUS_PARTIALLY_COMPLETED

                : BillingRun::STATUS_COMPLETED;

        $this->billingRun->update([

            'status' =>
                $status,

            'execution_completed_at' =>
                now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Run Number
    |--------------------------------------------------------------------------
    */

    protected function generateRunNumber(): string
    {
        $date =
            now()->format('Ym');

        $count =
            BillingRun::query()

                ->where(
                    'company_id',
                    $this->user->company_id
                )

                ->count()
                + 1;

        return sprintf(

            'BR-%s-%05d',

            $date,

            $count
        );
    }
}