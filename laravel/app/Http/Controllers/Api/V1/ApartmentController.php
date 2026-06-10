<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApartmentOwnershipHistoryResource;
use App\Http\Resources\Api\V1\ApartmentResource;
use App\Models\Apartment;
use App\Models\ApartmentOwnershipHistory;
use App\Services\Property\ApartmentInventoryService;
use App\Services\Property\BuildingPortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApartmentController extends Controller
{
    public function __construct(
        private readonly BuildingPortfolioService $buildings,
        private readonly ApartmentInventoryService $inventory,
    ) {}
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    )
    {
        $query = Apartment::query()
            ->with(['building', 'activeLease'])
            ->withCount([
                'agreements as blocking_leases_count' => fn ($q) => $q->whereIn(
                    'status',
                    ApartmentInventoryService::LEASE_BLOCKING_STATUSES
                ),
            ])
            ->where('company_id', $request->user()->company_id);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(

                function ($q) use (
                    $search
                ) {

                    $q->where(
                        'unit_number',
                        'ILIKE',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'property_type',
                        'ILIKE',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'notes',
                        'ILIKE',
                        "%{$search}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('building_id')) {

            $query->where(
                'building_id',
                $request->building_id
            );
        }

        if ($request->filled('listing_type')) {

            $query->where(
                'listing_type',
                $request->listing_type
            );
        }

        if ($request->filled('inventory_status')) {

            $query->where(
                'inventory_status',
                $request->inventory_status
            );
        }

        if ($request->filled('property_type')) {

            $query->where(
                'property_type',
                $request->property_type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [

            'unit_number',

            'floor',

            'bedrooms',

            'bathrooms',

            'market_rent_price',

            'market_sale_price',

            'created_at',
        ];

        $sortBy = $request->get(
            'sort_by',
            'created_at'
        );

        $sortDirection = $request->get(
            'sort_direction',
            'desc'
        );

        if (! in_array(
            $sortBy,
            $allowedSorts
        )) {

            $sortBy = 'created_at';
        }

        if (! in_array(
            $sortDirection,
            ['asc', 'desc']
        )) {

            $sortDirection = 'desc';
        }

        $query->orderBy(
            $sortBy,
            $sortDirection
        );

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(

            (int) $request->get(
                'per_page',
                15
            ),

            100
        );

        $apartments = $query->paginate(
            $perPage
        );

        return ApartmentResource::collection(
            $apartments
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): JsonResponse
    {
        $validated = $request->validate([

            'building_id' => [

                'required',

                'uuid',

                'exists:buildings,id',
            ],

            'unit_number' => [

                'required',

                'string',

                'max:50',
            ],

            'floor' => [

                'nullable',

                'integer',

                'min:0',

                'max:300',
            ],

            'bedrooms' => [

                'required',

                'integer',

                'min:0',

                'max:20',
            ],

            'bathrooms' => [

                'required',

                'integer',

                'min:0',

                'max:20',
            ],

            'area_sqm' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'property_type' => [

                'required',

                'string',

                'max:50',
            ],

            'listing_type' => [

                'required',

                Rule::in(
                    Apartment::LISTING_TYPES
                ),
            ],

            'inventory_status' => [

                'required',

                Rule::in(
                    Apartment::INVENTORY_STATUSES
                ),
            ],

            'market_rent_price' => [
                Rule::requiredIf(in_array(
                    $request->input('listing_type'),
                    [Apartment::LISTING_TYPE_RENTAL, Apartment::LISTING_TYPE_HYBRID],
                    true
                )),
                'nullable',
                'numeric',
                'min:0.01',
            ],

            'market_sale_price' => [
                Rule::requiredIf(in_array(
                    $request->input('listing_type'),
                    [Apartment::LISTING_TYPE_SALE, Apartment::LISTING_TYPE_HYBRID],
                    true
                )),
                'nullable',
                'numeric',
                'min:0.01',
            ],

            'security_deposit' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'currency' => [

                'required',

                'string',

                'max:10',
            ],

            'has_balcony' => [

                'boolean',
            ],

            'has_parking' => [

                'boolean',
            ],

            'has_storage' => [

                'boolean',
            ],

            'is_furnished' => [

                'boolean',
            ],

            'notes' => [

                'nullable',

                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Unit
        |--------------------------------------------------------------------------
        */

        $exists = Apartment::query()

            ->where(
                'company_id',
                $request->user()->company_id
            )

            ->where(
                'building_id',
                $validated['building_id']
            )

            ->where(
                'unit_number',
                $validated['unit_number']
            )

            ->exists();

        if ($exists) {

            return response()->json([

                'success' => false,

                'message' =>

                    'Apartment unit already exists in this building.',
            ], 422);
        }

        $this->buildings->assertBuildingBelongsToCompany(
            $validated['building_id'],
            $request->user()->company_id,
        );

        DB::beginTransaction();

        try {

            $apartment = Apartment::create([

                ...$validated,

                'company_id' =>

                    $request->user()->company_id,

                'created_by' =>

                    $request->user()->id,
            ]);

            $this->buildings->syncUnitCount(
                $apartment->building()->first()
            );

            DB::commit();

            return response()->json([

                'success' => true,

                'message' =>

                    'Apartment created successfully.',

                'data' =>

                    new ApartmentResource(

                        $apartment->load(
                            'building'
                        )
                    ),
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Failed to create apartment.',

                'error' =>

                    config('app.debug')
                        ? $e->getMessage()
                        : null,
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
        Apartment $apartment
    ): JsonResponse
    {
        abort_if(

            $apartment->company_id
            !== $request->user()->company_id,

            403,

            'Unauthorized access.'
        );

        return response()->json([

            'success' => true,

            'data' =>

                new ApartmentResource(
                    $apartment
                        ->load(['building', 'activeLease'])
                        ->loadCount([
                            'agreements as blocking_leases_count' => fn ($q) => $q->whereIn(
                                'status',
                                ApartmentInventoryService::LEASE_BLOCKING_STATUSES
                            ),
                        ])
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Apartment $apartment
    ): JsonResponse
    {
        abort_if(

            $apartment->company_id
            !== $request->user()->company_id,

            403,

            'Unauthorized access.'
        );

        $validated = $request->validate([

            'building_id' => [

                'sometimes',

                'uuid',

                'exists:buildings,id',
            ],

            'unit_number' => [

                'sometimes',

                'string',

                'max:50',
            ],

            'floor' => [

                'nullable',

                'integer',

                'min:0',

                'max:300',
            ],

            'bedrooms' => [

                'sometimes',

                'integer',

                'min:0',

                'max:20',
            ],

            'bathrooms' => [

                'sometimes',

                'integer',

                'min:0',

                'max:20',
            ],

            'area_sqm' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'property_type' => [

                'sometimes',

                'string',

                'max:50',
            ],

            'listing_type' => [

                'sometimes',

                Rule::in(
                    Apartment::LISTING_TYPES
                ),
            ],

            'inventory_status' => [

                'sometimes',

                Rule::in(
                    Apartment::INVENTORY_STATUSES
                ),
            ],

            'market_rent_price' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'market_sale_price' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'security_deposit' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'currency' => [

                'sometimes',

                'string',

                'max:10',
            ],

            'has_balcony' => [

                'boolean',
            ],

            'has_parking' => [

                'boolean',
            ],

            'has_storage' => [

                'boolean',
            ],

            'is_furnished' => [

                'boolean',
            ],

            'notes' => [

                'nullable',

                'string',
            ],
        ]);

        if (isset($validated['building_id'])) {
            $this->buildings->assertBuildingBelongsToCompany(
                $validated['building_id'],
                $request->user()->company_id,
            );
        }

        $inventoryStatus = $validated['inventory_status'] ?? null;
        if ($inventoryStatus !== null) {
            $this->inventory->assertInventoryStatusChangeAllowed(
                $apartment,
                $inventoryStatus,
            );
            unset($validated['inventory_status']);
        }

        DB::beginTransaction();

        try {

            $validated['updated_by'] =

                $request->user()->id;

            $apartment->update(
                $validated
            );

            if ($inventoryStatus !== null && $apartment->inventory_status !== $inventoryStatus) {
                $this->inventory->transitionStatus(
                    $apartment,
                    $inventoryStatus,
                    'Manual inventory update',
                );
            }

            DB::commit();

            return response()->json([

                'success' => true,

                'message' =>

                    'Apartment updated successfully.',

                'data' =>

                    new ApartmentResource(

                        $apartment
                            ->fresh()
                            ->load('building')
                    ),
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Failed to update apartment.',

                'error' =>

                    config('app.debug')
                        ? $e->getMessage()
                        : null,
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Apartment $apartment
    ): JsonResponse
    {
        abort_if(

            $apartment->company_id
            !== $request->user()->company_id,

            403,

            'Unauthorized access.'
        );

        try {

            $this->inventory->assertCanDelete($apartment);

            $building = $apartment->building;

            $apartment->delete();

            if ($building) {
                $this->buildings->syncUnitCount($building);
            }

            return response()->json([

                'success' => true,

                'message' =>

                    'Apartment deleted successfully.',
            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>

                    'Failed to delete apartment.',

                'error' =>

                    config('app.debug')
                        ? $e->getMessage()
                        : null,
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    public function summary(
        Request $request
    ): JsonResponse
    {
        $query = Apartment::query()

            ->where(
                'company_id',
                $request->user()->company_id
            );

        return response()->json([

            'success' => true,

            'data' => [

                'total' =>

                    (clone $query)->count(),

                'available' =>

                    (clone $query)

                        ->where(
                            'inventory_status',
                            Apartment::STATUS_AVAILABLE
                        )

                        ->count(),

                'occupied' =>

                    (clone $query)

                        ->where(
                            'inventory_status',
                            Apartment::STATUS_OCCUPIED
                        )

                        ->count(),

                'reserved' =>

                    (clone $query)

                        ->where(
                            'inventory_status',
                            Apartment::STATUS_RESERVED
                        )

                        ->count(),

                'under_contract' =>

                    (clone $query)

                        ->where(
                            'inventory_status',
                            Apartment::STATUS_UNDER_CONTRACT
                        )

                        ->count(),

                'sold' =>

                    (clone $query)

                        ->where(
                            'inventory_status',
                            Apartment::STATUS_SOLD
                        )

                        ->count(),

                'rental_units' =>

                    (clone $query)

                        ->whereIn(

                            'listing_type',

                            [

                                Apartment::LISTING_TYPE_RENTAL,

                                Apartment::LISTING_TYPE_HYBRID,
                            ]
                        )

                        ->count(),

                'sale_units' =>

                    (clone $query)

                        ->whereIn(

                            'listing_type',

                            [

                                Apartment::LISTING_TYPE_SALE,

                                Apartment::LISTING_TYPE_HYBRID,
                            ]
                        )

                        ->count(),
            ],
        ]);
    }

    public function ownershipHistory(Request $request, string $apartment): JsonResponse
    {
        $unit = Apartment::query()
            ->where('company_id', $request->user()->company_id)
            ->where('id', $apartment)
            ->first();

        if (! $unit) {
            return response()->json([
                'success' => false,
                'message' => 'Apartment not found.',
            ], 404);
        }

        $history = ApartmentOwnershipHistory::query()
            ->with(['buyer', 'saleAgreement.agreement', 'recordedBy'])
            ->where('apartment_id', $unit->id)
            ->orderByDesc('transfer_date')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Ownership history retrieved.',
            'data' => ApartmentOwnershipHistoryResource::collection($history),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}