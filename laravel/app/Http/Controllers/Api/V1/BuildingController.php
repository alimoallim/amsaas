<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Http\Requests\Api\V1\StoreBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Services\Property\BuildingPortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function __construct(
        private readonly BuildingPortfolioService $portfolio,
    ) {}
    /*
    |--------------------------------------------------------------------------
    | List Buildings
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Building::query()->withCount('apartments');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search')->trim().'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', $term)
                    ->orWhere('code', 'ILIKE', $term)
                    ->orWhere('city', 'ILIKE', $term)
                    ->orWhere('address', 'ILIKE', $term);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $buildings = $query
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return BuildingResource::collection(
            $buildings
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Building
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreBuildingRequest $request
    ) {

        $validated =
            $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        $validated['company_id'] = $request->user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Create Building
        |--------------------------------------------------------------------------
        */

        $building = Building::create([

            'company_id' =>

                $validated['company_id'],

            'name' =>

                $validated['name'],

            'code' =>

                $validated['code'] ?? null,

            'type' =>

                $validated['type'] ?? null,

            'address' =>

                $validated['address'] ?? null,

            'city' =>

                $validated['city'] ?? null,

            'country' =>

                $validated['country'] ?? null,

            'timezone' =>

                $validated['timezone'] ?? null,

            'operating_currency' =>

                $validated['operating_currency'],

            'total_floors' =>

                $validated['total_floors'] ?? 0,

            'total_units' =>

                $validated['total_units'] ?? 0,

            'description' =>

                $validated['description'] ?? null,

            'is_active' =>

                $validated['is_active'] ?? true,
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Building created successfully.',

            'data' =>

                new BuildingResource(
                    $building
                )

        ], 201);
    }
    /*
|--------------------------------------------------------------------------
| Update Building
|--------------------------------------------------------------------------
*/

public function update(
    StoreBuildingRequest $request,
    Building $building
) {

    $validated =
        $request->validated();

    $building->update([

        'name' =>

            $validated['name'],

        'code' =>

            $validated['code'] ?? null,

        'type' =>

            $validated['type'] ?? null,

        'address' =>

            $validated['address'] ?? null,

        'city' =>

            $validated['city'] ?? null,

        'country' =>

            $validated['country'] ?? null,

        'timezone' =>

            $validated['timezone'] ?? null,

        'operating_currency' =>

            $validated['operating_currency'],

        'total_floors' =>

            $validated['total_floors'] ?? 0,

        'total_units' =>

            $validated['total_units'] ?? 0,

        'description' =>

            $validated['description'] ?? null,

        'is_active' =>

            $validated['is_active'] ?? true,
    ]);

    return response()->json([

        'success' => true,

        'message' =>

            'Building updated successfully.',

        'data' => new BuildingResource(
            $building
        )
    ]);
}
/*
|--------------------------------------------------------------------------
| Show Building
|--------------------------------------------------------------------------
*/

public function show(
    Building $building
) {

    return response()->json([

        'success' => true,

        'data' => new BuildingResource(
            $building->loadCount('apartments')
        )
    ]);
}

/*
|--------------------------------------------------------------------------
| Delete Building
|--------------------------------------------------------------------------
*/

public function destroy(Building $building): JsonResponse
{
    $this->portfolio->assertCanDelete($building);
    $building->delete();

    return response()->json([
        'success' => true,
        'message' => 'Building deleted successfully.',
    ]);
}
}