<?php

namespace App\Actions\Billing;

use App\Models\User;
use App\Models\BillingRun;
use App\Services\Billing\BillingProcessorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class GenerateBillingRunAction
{
    /*
    |--------------------------------------------------------------------------
    | Execute Billing Run
    |--------------------------------------------------------------------------
    */

    public function execute(

        User $user,

        string $frequency = BillingRun::FREQUENCY_MONTHLY,

        ?Carbon $billingDate = null,

        bool $dryRun = false

    ): BillingRun {

        $billingDate =
            $billingDate
            ??
            now();

        /*
        |--------------------------------------------------------------------------
        | Prevent Concurrent Billing Runs
        |--------------------------------------------------------------------------
        */

        $this->ensureNoActiveBillingRun(

            companyId:
                $user->company_id,

            frequency:
                $frequency
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Period Billing
        |--------------------------------------------------------------------------
        */

        $this->ensureBillingPeriodNotProcessed(

            companyId:
                $user->company_id,

            frequency:
                $frequency,

            billingDate:
                $billingDate
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Financial Billing Engine
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Initialize Processor
            |--------------------------------------------------------------------------
            */

            $processor =
                new BillingProcessorService(

                    user:
                        $user,

                    billingDate:
                        $billingDate
                );

            /*
            |--------------------------------------------------------------------------
            | Process Billing
            |--------------------------------------------------------------------------
            */

            $billingRun =
                $processor->process();

            /*
            |--------------------------------------------------------------------------
            | Mark Dry Run
            |--------------------------------------------------------------------------
            */

            if (
                $dryRun
            ) {

                $billingRun->update([

                    'is_dry_run' =>
                        true,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Lock Billing Run
            |--------------------------------------------------------------------------
            */

            $billingRun->update([

                'is_locked' =>
                    true,
            ]);

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            Log::info(

                'Billing run generated successfully.',

                [

                    'billing_run_id' =>
                        $billingRun->id,

                    'run_number' =>
                        $billingRun->run_number,

                    'company_id' =>
                        $billingRun->company_id,

                    'generated_by' =>
                        $user->id,
                ]
            );

            return $billingRun;
        }

        catch (Throwable $exception) {

            DB::rollBack();

            Log::critical(

                'Billing run generation failed.',

                [

                    'company_id' =>
                        $user->company_id,

                    'user_id' =>
                        $user->id,

                    'message' =>
                        $exception->getMessage(),

                    'trace' =>
                        $exception->getTraceAsString(),
                ]
            );

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Prevent Concurrent Billing Execution
    |--------------------------------------------------------------------------
    */

    protected function ensureNoActiveBillingRun(

        string $companyId,

        string $frequency

    ): void {

        $activeRunExists =
            BillingRun::query()

                ->where(
                    'company_id',
                    $companyId
                )

                ->where(
                    'billing_frequency',
                    $frequency
                )

                ->whereIn(

                    'status',

                    [

                        BillingRun::STATUS_PENDING,

                        BillingRun::STATUS_RUNNING,
                    ]
                )

                ->exists();

        if (
            $activeRunExists
        ) {

            throw ValidationException::withMessages([

                'billing_run' => [

                    'Another billing run is already executing.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Billing Period Processing
    |--------------------------------------------------------------------------
    */

    protected function ensureBillingPeriodNotProcessed(

        string $companyId,

        string $frequency,

        Carbon $billingDate

    ): void {

        $periodStart =
            $this->resolvePeriodStart(

                frequency:
                    $frequency,

                billingDate:
                    $billingDate
            );

        $periodEnd =
            $this->resolvePeriodEnd(

                frequency:
                    $frequency,

                billingDate:
                    $billingDate
            );

        $alreadyProcessed =
            BillingRun::query()

                ->where(
                    'company_id',
                    $companyId
                )

                ->where(
                    'billing_frequency',
                    $frequency
                )

                ->whereDate(
                    'billing_period_start',
                    $periodStart
                )

                ->whereDate(
                    'billing_period_end',
                    $periodEnd
                )

                ->whereIn(

                    'status',

                    [

                        BillingRun::STATUS_COMPLETED,

                        BillingRun::STATUS_PARTIALLY_COMPLETED,
                    ]
                )

                ->exists();

        if (
            $alreadyProcessed
        ) {

            throw ValidationException::withMessages([

                'billing_period' => [

                    'Billing has already been processed for this period.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Billing Period Start
    |--------------------------------------------------------------------------
    */

    protected function resolvePeriodStart(

        string $frequency,

        Carbon $billingDate

    ): Carbon {

        return match ($frequency) {

            BillingRun::FREQUENCY_DAILY =>
                $billingDate
                    ->copy()
                    ->startOfDay(),

            BillingRun::FREQUENCY_WEEKLY =>
                $billingDate
                    ->copy()
                    ->startOfWeek(),

            BillingRun::FREQUENCY_MONTHLY =>
                $billingDate
                    ->copy()
                    ->startOfMonth(),

            BillingRun::FREQUENCY_QUARTERLY =>
                $billingDate
                    ->copy()
                    ->startOfQuarter(),

            BillingRun::FREQUENCY_YEARLY =>
                $billingDate
                    ->copy()
                    ->startOfYear(),

            default =>
                $billingDate
                    ->copy()
                    ->startOfMonth(),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Billing Period End
    |--------------------------------------------------------------------------
    */

    protected function resolvePeriodEnd(

        string $frequency,

        Carbon $billingDate

    ): Carbon {

        return match ($frequency) {

            BillingRun::FREQUENCY_DAILY =>
                $billingDate
                    ->copy()
                    ->endOfDay(),

            BillingRun::FREQUENCY_WEEKLY =>
                $billingDate
                    ->copy()
                    ->endOfWeek(),

            BillingRun::FREQUENCY_MONTHLY =>
                $billingDate
                    ->copy()
                    ->endOfMonth(),

            BillingRun::FREQUENCY_QUARTERLY =>
                $billingDate
                    ->copy()
                    ->endOfQuarter(),

            BillingRun::FREQUENCY_YEARLY =>
                $billingDate
                    ->copy()
                    ->endOfYear(),

            default =>
                $billingDate
                    ->copy()
                    ->endOfMonth(),
        };
    }
}