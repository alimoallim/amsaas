<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BulkMeterReadingApprovalRequest;
use App\Http\Requests\Api\V1\BulkMeterReadingRequest;
use App\Http\Resources\Api\V1\MeterReadingResource;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\MeterReading\BulkMeterReadingApprovalService;
use App\Services\MeterReading\BulkMeterReadingService;
use App\Services\MeterReading\MeterReadingEntryGridService;
use App\Services\MeterReading\MeterReadingProcessorService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

            ->when(
                $request->filled('meter_id'),
                fn ($query) => $query->where('meter_id', $request->meter_id)
            )

            ->when(
                $request->filled('reading_date'),
                fn ($query) => $query->whereDate('reading_date', $request->reading_date)
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

                    Rule::unique('meter_readings', 'reading_date')
                        ->where(fn ($query) => $query->where(
                            'meter_id',
                            $request->input('meter_id')
                        )),
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
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'reading_date' => [
                    'A reading for this meter already exists on this date. Edit the existing reading or choose a different date.',
                ],
            ]);
        }

        $reading->load([
            'meter',
            'building',
            'apartment',
            'reader',
            'approver',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meter reading captured successfully.',
            'data' => new MeterReadingResource($reading),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        MeterReading $meterReading
    ): JsonResponse {
        abort_unless(
            $meterReading->company_id === $request->user()->company_id,
            404
        );

        $validated = $request->validate([
            'meter_id' => ['sometimes', 'uuid', 'exists:meters,id'],
            'reading_date' => [
                'sometimes',
                'date',
                Rule::unique('meter_readings', 'reading_date')
                    ->where(fn ($query) => $query->where(
                        'meter_id',
                        $request->input('meter_id', $meterReading->meter_id)
                    ))
                    ->ignore($meterReading->id),
            ],
            'current_reading' => ['sometimes', 'numeric', 'min:0'],
            'reading_type' => ['nullable', Rule::in(MeterReading::READING_TYPES)],
            'reading_source' => ['nullable', Rule::in(MeterReading::READING_SOURCES)],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $service = new MeterReadingProcessorService($request->user());
            $reading = $service->update($meterReading, $validated);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'reading_date' => [
                    'A reading for this meter already exists on this date. Choose a different date.',
                ],
            ]);
        }

        $reading->load(['meter', 'building', 'apartment', 'reader', 'approver']);

        return response()->json([
            'success' => true,
            'message' => 'Meter reading updated. Re-approve the reading if utility charges should be regenerated.',
            'data' => new MeterReadingResource($reading),
        ]);
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
    | Bulk entry grid
    |--------------------------------------------------------------------------
    */

    public function entryGrid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reading_date' => ['required', 'date'],
            'building_id' => ['nullable', 'uuid', 'exists:buildings,id'],
            'utility_type' => ['nullable', 'string', 'in:'.implode(',', Meter::UTILITY_TYPES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $grid = (new MeterReadingEntryGridService($request->user()))
            ->paginate($validated);

        return response()->json([
            'success' => true,
            'message' => 'Meter reading entry grid retrieved successfully.',
            'data' => $grid->items(),
            'meta' => [
                'current_page' => $grid->currentPage(),
                'last_page' => $grid->lastPage(),
                'per_page' => $grid->perPage(),
                'total' => $grid->total(),
                'from' => $grid->firstItem(),
                'to' => $grid->lastItem(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk store
    |--------------------------------------------------------------------------
    */

    public function bulkApprove(BulkMeterReadingApprovalRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = (new BulkMeterReadingApprovalService($request->user()))
            ->approve($validated['reading_ids']);

        $message = match (true) {
            $result['failed'] > 0 && $result['approved'] > 0 =>
                'Meter readings partially approved.',
            $result['failed'] > 0 =>
                'Meter readings could not be approved.',
            $result['approved'] === 0 =>
                'No meter readings were approved.',
            default =>
                'Meter readings approved successfully.',
        };

        return response()->json([
            'success' => $result['failed'] === 0 && $result['approved'] > 0,
            'message' => $message,
            'data' => $result,
        ], $result['failed'] > 0 && $result['approved'] > 0 ? 207 : ($result['approved'] > 0 ? 200 : 422));
    }

    public function bulkStore(BulkMeterReadingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = (new BulkMeterReadingService($request->user()))
            ->store(
                $validated['reading_date'],
                $validated['readings'],
            );

        $message = match (true) {
            $result['failed'] > 0 && $result['saved'] > 0 =>
                'Bulk meter readings partially saved.',
            $result['failed'] > 0 =>
                'Bulk meter readings could not be saved.',
            default =>
                'Bulk meter readings saved successfully.',
        };

        return response()->json([
            'success' => $result['failed'] === 0,
            'message' => $message,
            'data' => $result,
        ], $result['failed'] > 0 && $result['saved'] > 0 ? 207 : ($result['saved'] > 0 ? 200 : 422));
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
