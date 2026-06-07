<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Meter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

use App\Services\MeterLifecycleService;

use App\Http\Controllers\Controller;

use App\Http\Requests\Api\V1\StoreMeterRequest;
use App\Http\Requests\Api\V1\UpdateMeterRequest;

use App\Http\Resources\Api\V1\MeterResource;

class MeterController extends Controller
{

    use AuthorizesRequests;
    public function __construct(
        protected MeterLifecycleService $meterLifecycleService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Meter Registry
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $query = Meter::query()

            ->with([

                'company',

                'building',

                'apartment',

                'tenant',

                'creator',

                'updater',
            ])

            ->where(

                'company_id',

                $request->user()->company_id
            );

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'utility_type'
            )
        ) {

            $query->where(

                'utility_type',

                $request->utility_type
            );
        }

        if (
            $request->filled(
                'ownership_type'
            )
        ) {

            $query->where(

                'ownership_type',

                $request->ownership_type
            );
        }

        if (
            $request->filled(
                'status'
            )
        ) {

            $query->where(

                'status',

                $request->status
            );
        }

        if (
            $request->filled(
                'building_id'
            )
        ) {

            $query->forBuilding(
                $request->string('building_id')->toString()
            );
        }

        if (
            $request->filled(
                'apartment_id'
            )
        ) {

            $query->where(

                'apartment_id',

                $request->apartment_id
            );
        }

        if (
            $request->filled(
                'tenant_id'
            )
        ) {

            $query->where(

                'tenant_id',

                $request->tenant_id
            );
        }

        if (
            $request->boolean(
                'smart_meter'
            )
        ) {

            $query->smart();
        }

        if (
            $request->boolean(
                'shared'
            )
        ) {

            $query->shared();
        }

        if (
            $request->boolean(
                'maintenance_required'
            )
        ) {

            $query->requiresMaintenance();
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'search'
            )
        ) {

            $search =
                $request->search;

            $query->where(

                function ($q)
                use ($search) {

                    $q->where(

                        'meter_number',

                        'like',

                        "%{$search}%"
                    )

                    ->orWhere(

                        'serial_number',

                        'like',

                        "%{$search}%"
                    )

                    ->orWhere(

                        'manufacturer',

                        'like',

                        "%{$search}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $query->latest();

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $meters = $query->paginate(

            $request->integer(
                'per_page',
                15
            )
        );

        /*
        |--------------------------------------------------------------------------
        | KPI Summary
        |--------------------------------------------------------------------------
        */

        $baseQuery = Meter::query()

            ->where(

                'company_id',

                $request->user()->company_id
            );

        $summary = [

            'total' =>

                (clone $baseQuery)->count(),

            'active' =>

                (clone $baseQuery)

                    ->where(
                        'status',
                        Meter::STATUS_ACTIVE
                    )

                    ->count(),

            'faulty' =>

                (clone $baseQuery)

                    ->where(
                        'status',
                        Meter::STATUS_FAULTY
                    )

                    ->count(),

            'maintenance' =>

                (clone $baseQuery)

                    ->where(
                        'maintenance_required',
                        true
                    )

                    ->count(),

            'smart' =>

                (clone $baseQuery)

                    ->where(
                        'meter_type',
                        Meter::TYPE_SMART
                    )

                    ->count(),

            'shared' =>

                (clone $baseQuery)

                    ->where(
                        'is_shared',
                        true
                    )

                    ->count(),
        ];

        return response()->json([

            'success' => true,

            'message' =>

                'Meters retrieved successfully.',

            'data' =>

                MeterResource::collection(
                    $meters
                ),

            'summary' =>

                $summary,

            'meta' => [

                'current_page' =>

                    $meters->currentPage(),

                'last_page' =>

                    $meters->lastPage(),

                'per_page' =>

                    $meters->perPage(),

                'total' =>

                    $meters->total(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Meter
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreMeterRequest $request
    ): JsonResponse {
        
        $meter = Meter::create([

            ...$request->validated(),

            'company_id' =>

                $request->user()->company_id,

            'created_by' =>

                $request->user()->id,

            'updated_by' =>

                $request->user()->id,
        ]);

        $meter->load([

            'company',

            'building',

            'apartment',

            'tenant',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Meter registered successfully.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Meter
    |--------------------------------------------------------------------------
    */

    public function show(
        Meter $meter
    ): JsonResponse {
        $this->authorize(
            'view',
            $meter
        );

        $meter->load([

            'company',

            'building',

            'apartment',

            'tenant',

            'creator',

            'updater',

            'replacementMeter',

            'readings',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Meter retrieved successfully.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Meter
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateMeterRequest $request,
        Meter $meter
    ): JsonResponse {

        $this->authorize(
            'update',
            $meter
        );

        $meter->update([

            ...$request->validated(),

            'updated_by' =>

                $request->user()->id,
        ]);

        $meter->refresh();

        return response()->json([

            'success' => true,

            'message' =>

                'Meter updated successfully.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Meter
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Meter $meter
    ): JsonResponse {

        $this->authorize(
            'delete',
            $meter
        );

        $meter->delete();

        return response()->json([

            'success' => true,

            'message' =>

                'Meter deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Activate Meter
    |--------------------------------------------------------------------------
    */

    public function activate(
        Meter $meter
    ): JsonResponse {

        $meter =
            $this->meterLifecycleService
                ->activate($meter);

        return response()->json([

            'success' => true,

            'message' =>

                'Meter activated successfully.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Faulty
    |--------------------------------------------------------------------------
    */

    public function markFaulty(
        Request $request,
        Meter $meter
    ): JsonResponse {

        $meter =
            $this->meterLifecycleService
                ->markFaulty(

                    meter:
                        $meter,

                    reason:
                        $request->reason
                );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter marked as faulty.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Maintenance
    |--------------------------------------------------------------------------
    */

    public function maintenance(
        Request $request,
        Meter $meter
    ): JsonResponse {

        $meter =
            $this->meterLifecycleService
                ->markUnderMaintenance(

                    meter:
                        $meter,

                    reason:
                        $request->reason
                );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter moved to maintenance.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Maintenance
    |--------------------------------------------------------------------------
    */

    public function completeMaintenance(
        Request $request,
        Meter $meter
    ): JsonResponse {

        $meter =
            $this->meterLifecycleService
                ->completeMaintenance(

                    meter:
                        $meter,

                    note:
                        $request->note
                );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter maintenance completed.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Decommission
    |--------------------------------------------------------------------------
    */

    public function decommission(
        Request $request,
        Meter $meter
    ): JsonResponse {

        $meter =
            $this->meterLifecycleService
                ->decommission(

                    meter:
                        $meter,

                    reason:
                        $request->reason
                );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter decommissioned successfully.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Inspection
    |--------------------------------------------------------------------------
    */

    public function completeInspection(
        Request $request,
        Meter $meter
    ): JsonResponse {

        $meter =
            $this->meterLifecycleService
                ->completeInspection(

                    meter:
                        $meter,

                    note:
                        $request->note
                );

        return response()->json([

            'success' => true,

            'message' =>

                'Meter inspection completed.',

            'data' =>

                new MeterResource(
                    $meter
                ),
        ]);
    }
}