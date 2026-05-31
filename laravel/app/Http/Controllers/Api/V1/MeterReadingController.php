<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MeterReadingResource;
use App\Models\MeterReading;
use App\Services\MeterReading\MeterReadingProcessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MeterReadingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $readings = MeterReading::query()

            ->with([

                'meter',

                'building',

                'apartment',

                'reader',

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

                    $query->whereHas(

                        'meter',

                        fn ($meterQuery) =>

                        $meterQuery->where(

                            'meter_number',

                            'like',

                            "%{$search}%"
                        )
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
            | Utility Type Filter
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->filled(
                    'utility_type'
                ),

                function (
                    $query
                ) use (
                    $request
                ) {

                    $query->whereHas(

                        'meter',

                        fn ($meterQuery) =>

                        $meterQuery->where(

                            'utility_type',

                            $request
                                ->utility_type
                        )
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Anomaly Filter
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->boolean(
                    'anomalies_only'
                ),

                fn ($query) =>

                $query->where(
                    'anomaly_detected',
                    true
                )
            )

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            ->latest(
                'reading_date'
            )

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

                'Meter readings retrieved successfully.',

            'data' =>

                MeterReadingResource::collection(
                    $readings
                ),

            'meta' => [

                'current_page' =>
                    $readings->currentPage(),

                'last_page' =>
                    $readings->lastPage(),

                'per_page' =>
                    $readings->perPage(),

                'total' =>
                    $readings->total(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): JsonResponse {

        $validated =
            $request->validate([

                'meter_id' => [

                    'required',

                    'exists:meters,id',
                ],

                'reading_date' => [

                    'required',

                    'date',
                ],

                'current_reading' => [

                    'required',

                    'numeric',

                    'min:0',
                ],

                'reading_type' => [

                    'nullable',

                    Rule::in(
                        MeterReading::READING_TYPES
                    ),
                ],

                'reading_source' => [

                    'nullable',

                    Rule::in(
                        MeterReading::READING_SOURCES
                    ),
                ],

                'notes' => [

                    'nullable',

                    'string',
                ],
            ]);

        try {

            $service =
                new MeterReadingProcessorService(

                    $request->user()
                );

            $reading =
                $service->process(
                    $validated
                );

            $reading->load([

                'meter',

                'building',

                'apartment',

                'reader',

                'approver',
            ]);

            return response()->json([

                'success' => true,

                'message' =>

                    'Meter reading captured successfully.',

                'data' =>

                    new MeterReadingResource(
                        $reading
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

                    'Meter reading processing failed.',

                'error' =>

                    app()->environment(
                        'local'
                    )

                    ? $exception->getMessage()

                    : 'Unexpected operational failure.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        MeterReading $meterReading
    ): JsonResponse {

        abort_unless(

            $meterReading->company_id
            ===
            $request
                ->user()
                ->company_id,

            404
        );

        $meterReading->load([

            'meter',

            'building',

            'apartment',

            'reader',

            'approver',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Meter reading retrieved successfully.',

            'data' =>

                new MeterReadingResource(
                    $meterReading
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approve
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        MeterReading $meterReading
    ): JsonResponse {

        abort_unless(

            $meterReading->company_id
            ===
            $request
                ->user()
                ->company_id,

            404
        );

        $service =
            new MeterReadingProcessorService(

                $request->user()
            );

        $reading =
            $service->approve(
                $meterReading
            );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter reading approved successfully.',

            'data' =>

                new MeterReadingResource(
                    $reading
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reject
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        MeterReading $meterReading
    ): JsonResponse {

        abort_unless(

            $meterReading->company_id
            ===
            $request
                ->user()
                ->company_id,

            404
        );

        $validated =
            $request->validate([

                'reason' => [

                    'nullable',

                    'string',
                ],
            ]);

        $service =
            new MeterReadingProcessorService(

                $request->user()
            );

        $reading =
            $service->reject(

                reading:
                    $meterReading,

                reason:
                    $validated['reason']
                    ?? null
            );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter reading rejected successfully.',

            'data' =>

                new MeterReadingResource(
                    $reading
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Anomalies
    |--------------------------------------------------------------------------
    */

    public function anomalies(
        Request $request
    ): JsonResponse {

        $readings = MeterReading::query()

            ->with([

                'meter',

                'building',

                'apartment',
            ])

            ->where(

                'company_id',

                $request
                    ->user()
                    ->company_id
            )

            ->where(
                'anomaly_detected',
                true
            )

            ->latest(
                'reading_date'
            )

            ->paginate(15);

        return response()->json([

            'success' => true,

            'message' =>

                'Anomalous meter readings retrieved successfully.',

            'data' =>

                MeterReadingResource::collection(
                    $readings
                ),
        ]);
    }
}