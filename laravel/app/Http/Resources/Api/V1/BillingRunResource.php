<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingRunResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */

    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Financial Totals
        |--------------------------------------------------------------------------
        */

        $subtotal =
            (float)
            $this->subtotal_amount;

        $tax =
            (float)
            $this->tax_amount;

        $penalty =
            (float)
            $this->penalty_amount;

        $discount =
            (float)
            $this->discount_amount;

        $total =
            (float)
            $this->total_amount;

        /*
        |--------------------------------------------------------------------------
        | Processing Metrics
        |--------------------------------------------------------------------------
        */

        $generatedItems =
            (int)
            $this->total_billing_items_generated;

        $successCount =
            (int)
            $this->success_count;

        $failureCount =
            (int)
            $this->failure_count;

        $completionPercentage =
            $generatedItems > 0

            ? round(

                (
                    $successCount
                    /
                    $generatedItems
                ) * 100,

                2
            )

            : 0;

        /*
        |--------------------------------------------------------------------------
        | Execution Duration
        |--------------------------------------------------------------------------
        */

        $executionDuration =
            $this->executionDurationInSeconds();

        return [

            /*
            |--------------------------------------------------------------------------
            | Core Identification
            |--------------------------------------------------------------------------
            */

            'id' =>

                $this->id,

            'run_number' =>

                $this->run_number,

            'name' =>

                $this->name,

            'description' =>

                $this->description,

            /*
            |--------------------------------------------------------------------------
            | Billing Configuration
            |--------------------------------------------------------------------------
            */

            'billing_frequency' => [

                'value' =>

                    $this->billing_frequency,

                'label' =>

                    str(
                        $this->billing_frequency
                    )

                    ->replace(
                        '_',
                        ' '
                    )

                    ->title(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Billing Period
            |--------------------------------------------------------------------------
            */

            'billing_period' => [

                'start_date' =>

                    optional(
                        $this->billing_period_start
                    )->toDateString(),

                'end_date' =>

                    optional(
                        $this->billing_period_end
                    )->toDateString(),

                'formatted' =>

                    optional(
                        $this->billing_period_start
                    )?->format(
                        'M d, Y'
                    )

                    .
                    ' - '
                    .
                    optional(
                        $this->billing_period_end
                    )?->format(
                        'M d, Y'
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Status Information
            |--------------------------------------------------------------------------
            */

            'status' => [

                'value' =>

                    $this->status,

                'label' =>

                    str(
                        $this->status
                    )

                    ->replace(
                        '_',
                        ' '
                    )

                    ->title(),

                'color' =>

                    $this->resolveStatusColor(),

                'is_running' =>

                    $this->isRunning(),

                'is_completed' =>

                    $this->isCompleted(),

                'is_failed' =>

                    $this->isFailed(),

                'is_locked' =>

                    $this->isLocked(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Financial Totals
            |--------------------------------------------------------------------------
            */

            'financial_summary' => [

                'subtotal_amount' =>

                    $subtotal,

                'tax_amount' =>

                    $tax,

                'penalty_amount' =>

                    $penalty,

                'discount_amount' =>

                    $discount,

                'total_amount' =>

                    $total,

                'currency' =>

                    $this->currency,

                'formatted_total' =>

                    $this->currency
                    .
                    ' '
                    .
                    number_format(
                        $total,
                        2
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Processing Metrics
            |--------------------------------------------------------------------------
            */

            'metrics' => [

                'total_agreements_processed' =>

                    (int)
                    $this->total_agreements_processed,

                'total_billing_items_generated' =>

                    $generatedItems,

                'total_successful_items' =>

                    (int)
                    $this->total_successful_items,

                'total_failed_items' =>

                    (int)
                    $this->total_failed_items,

                'success_count' =>

                    $successCount,

                'failure_count' =>

                    $failureCount,

                'completion_percentage' =>

                    $completionPercentage,
            ],

            /*
            |--------------------------------------------------------------------------
            | Execution Timeline
            |--------------------------------------------------------------------------
            */

            'execution' => [

                'scheduled_at' =>

                    optional(
                        $this->scheduled_at
                    )?->toDateTimeString(),

                'execution_started_at' =>

                    optional(
                        $this->execution_started_at
                    )?->toDateTimeString(),

                'execution_completed_at' =>

                    optional(
                        $this->execution_completed_at
                    )?->toDateTimeString(),

                'execution_duration_seconds' =>

                    $executionDuration,

                'execution_duration_human' =>

                    $executionDuration

                    ? gmdate(
                        'H:i:s',
                        $executionDuration
                    )

                    : null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Controls
            |--------------------------------------------------------------------------
            */

            'controls' => [

                'is_dry_run' =>

                    (bool)
                    $this->is_dry_run,

                'is_locked' =>

                    (bool)
                    $this->is_locked,

                'can_execute' =>

                    $this->canExecute(),

                'can_retry' =>

                    in_array(

                        $this->status,

                        [

                            'failed',

                            'partially_completed',
                        ]
                    ),

                'can_cancel' =>

                    !$this->isCompleted(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Insights
            |--------------------------------------------------------------------------
            */

            'health' => [

                'health_score' =>

                    $this->calculateHealthScore(),

                'has_failures' =>

                    $failureCount > 0,

                'requires_attention' =>

                    $failureCount > 0
                    ||
                    $completionPercentage < 100,

                'warning_level' =>

                    $this->resolveWarningLevel(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Audit Information
            |--------------------------------------------------------------------------
            */

            'audit' => [

                'executed_by' =>

                    $this->whenLoaded(

                        'executor',

                        fn () => [

                            'id' =>

                                $this->executor?->id,

                            'name' =>

                                $this->executor?->name,
                        ]
                    ),

                'approved_by' =>

                    $this->whenLoaded(

                        'approver',

                        fn () => [

                            'id' =>

                                $this->approver?->id,

                            'name' =>

                                $this->approver?->name,
                        ]
                    ),

                'approved_at' =>

                    optional(
                        $this->approved_at
                    )?->toDateTimeString(),

                'created_at' =>

                    optional(
                        $this->created_at
                    )?->toDateTimeString(),

                'updated_at' =>

                    optional(
                        $this->updated_at
                    )?->toDateTimeString(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Error Information
            |--------------------------------------------------------------------------
            */

            'errors' => [

                'error_summary' =>

                    $this->error_summary,
            ],

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            'metadata' =>

                $this->metadata,

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            'billing_items_count' =>

                $this->whenCounted(
                    'billingItems'
                ),

            /*
            |--------------------------------------------------------------------------
            | ERP Operational Actions
            |--------------------------------------------------------------------------
            */

            'available_actions' => [

                'view',

                'download_report',

                'retry',

                'cancel',

                'export',

                'generate_invoice',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Status Color
    |--------------------------------------------------------------------------
    */

    protected function resolveStatusColor(): string
    {
        return match (
            $this->status
        ) {

            'completed' =>
                'green',

            'running' =>
                'blue',

            'pending' =>
                'yellow',

            'failed' =>
                'red',

            'partially_completed' =>
                'orange',

            'cancelled' =>
                'gray',

            default =>
                'slate',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Warning Level
    |--------------------------------------------------------------------------
    */

    protected function resolveWarningLevel(): string
    {
        if (
            $this->failure_count > 10
        ) {

            return 'critical';
        }

        if (
            $this->failure_count > 0
        ) {

            return 'warning';
        }

        return 'normal';
    }

    /*
    |--------------------------------------------------------------------------
    | Health Score
    |--------------------------------------------------------------------------
    */

    protected function calculateHealthScore(): int
    {
        $generated =
            max(
                1,
                (int)
                $this->total_billing_items_generated
            );

        $failed =
            (int)
            $this->failure_count;

        $score =
            100 -
            (
                (
                    $failed
                    /
                    $generated
                ) * 100
            );

        return max(
            0,
            min(
                100,
                round($score)
            )
        );
    }
}