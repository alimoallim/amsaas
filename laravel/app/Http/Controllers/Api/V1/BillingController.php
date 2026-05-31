<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\GenerateBillingRunAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BillingRunResource;
use App\Models\BillingRun;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class BillingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Display Billing Runs
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $billingRuns = BillingRun::query()

            ->with([

                'executor',

                'approver',
            ])

            ->where(

                'company_id',

                $request
                    ->user()
                    ->company_id
            )

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->filled(
                    'search'
                ),

                function (
                    $query
                ) use (
                    $request
                ) {

                    $search =
                        $request->search;

                    $query->where(

                        function (
                            $query
                        ) use (
                            $search
                        ) {

                            $query->where(

                                'run_number',

                                'like',

                                "%{$search}%"
                            )

                            ->orWhere(

                                'name',

                                'like',

                                "%{$search}%"
                            );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Status Filter
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->filled(
                    'status'
                ),

                fn ($query) =>

                $query->where(

                    'status',

                    $request->status
                )
            )

            /*
            |--------------------------------------------------------------------------
            | Frequency Filter
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->filled(
                    'billing_frequency'
                ),

                fn ($query) =>

                $query->where(

                    'billing_frequency',

                    $request
                        ->billing_frequency
                )
            )

            /*
            |--------------------------------------------------------------------------
            | Date Range
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->filled(
                    'from_date'
                ),

                fn ($query) =>

                $query->whereDate(

                    'billing_period_start',

                    '>=',

                    $request->from_date
                )
            )

            ->when(

                $request->filled(
                    'to_date'
                ),

                fn ($query) =>

                $query->whereDate(

                    'billing_period_end',

                    '<=',

                    $request->to_date
                )
            )

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            ->latest()

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            ->paginate(

                $request->integer(
                    'per_page',
                    15
                )
            );

        return response()->json([

            'success' => true,

            'message' =>

                'Billing runs retrieved successfully.',

            'data' =>

                BillingRunResource::collection(
                    $billingRuns
                ),

            'meta' => [

                'current_page' =>
                    $billingRuns
                        ->currentPage(),

                'last_page' =>
                    $billingRuns
                        ->lastPage(),

                'per_page' =>
                    $billingRuns
                        ->perPage(),

                'total' =>
                    $billingRuns
                        ->total(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Single Billing Run
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        BillingRun $billingRun
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        abort_unless(

            $billingRun->company_id
            ===
            $request
                ->user()
                ->company_id,

            404
        );

        $billingRun->load([

            'billingItems',

            'executor',

            'approver',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Billing run retrieved successfully.',

            'data' =>

                new BillingRunResource(
                    $billingRun
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Billing Run
    |--------------------------------------------------------------------------
    */

    public function generate(
        Request $request,
        GenerateBillingRunAction $action
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

                'billing_frequency' => [

                    'nullable',

                    Rule::in(

                        BillingRun::FREQUENCIES
                    ),
                ],

                'billing_date' => [

                    'nullable',

                    'date',
                ],

                'dry_run' => [

                    'nullable',

                    'boolean',
                ],
            ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Execute Billing Action
            |--------------------------------------------------------------------------
            */

            $billingRun =
                $action->execute(

                    user:
                        $request->user(),

                    frequency:
                        $validated[
                            'billing_frequency'
                        ]
                        ??
                        BillingRun::FREQUENCY_MONTHLY,

                    billingDate:
                        isset(
                            $validated[
                                'billing_date'
                            ]
                        )

                        ? Carbon::parse(

                            $validated[
                                'billing_date'
                            ]
                        )

                        : now(),

                    dryRun:
                        $validated[
                            'dry_run'
                        ]
                        ??
                        false
                );

            /*
            |--------------------------------------------------------------------------
            | Reload Relations
            |--------------------------------------------------------------------------
            */

            $billingRun->load([

                'executor',

                'approver',
            ]);

            return response()->json([

                'success' => true,

                'message' =>

                    'Billing run generated successfully.',

                'data' =>

                    new BillingRunResource(
                        $billingRun
                    ),
            ], 201);
        }

        catch (Throwable $exception) {

            report(
                $exception
            );

            return response()->json([

                'success' => false,

                'message' =>

                    'Billing run generation failed.',

                'error' =>

                    app()->environment(
                        'local'
                    )

                    ? $exception->getMessage()

                    : 'Unexpected billing engine failure.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Retry Failed Billing Run
    |--------------------------------------------------------------------------
    */

    public function retry(
        Request $request,
        BillingRun $billingRun,
        GenerateBillingRunAction $action
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        abort_unless(

            $billingRun->company_id
            ===
            $request
                ->user()
                ->company_id,

            404
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Retry Eligibility
        |--------------------------------------------------------------------------
        */

        if (

            !in_array(

                $billingRun->status,

                [

                    BillingRun::STATUS_FAILED,

                    BillingRun::STATUS_PARTIALLY_COMPLETED,
                ]
            )
        ) {

            return response()->json([

                'success' => false,

                'message' =>

                    'Only failed billing runs can be retried.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Execute Retry
        |--------------------------------------------------------------------------
        */

        $newRun =
            $action->execute(

                user:
                    $request->user(),

                frequency:
                    $billingRun
                        ->billing_frequency,

                billingDate:
                    Carbon::parse(

                        $billingRun
                            ->billing_period_start
                    )
            );

        return response()->json([

            'success' => true,

            'message' =>

                'Billing run retried successfully.',

            'data' =>

                new BillingRunResource(
                    $newRun
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Billing Run
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        BillingRun $billingRun
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        abort_unless(

            $billingRun->company_id
            ===
            $request
                ->user()
                ->company_id,

            404
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Status
        |--------------------------------------------------------------------------
        */

        if (

            $billingRun->isCompleted()
        ) {

            return response()->json([

                'success' => false,

                'message' =>

                    'Completed billing runs cannot be cancelled.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Cancel Billing Run
        |--------------------------------------------------------------------------
        */

        $billingRun->update([

            'status' =>
                BillingRun::STATUS_CANCELLED,
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Billing run cancelled successfully.',

            'data' =>

                new BillingRunResource(
                    $billingRun
                ),
        ]);
    }
}