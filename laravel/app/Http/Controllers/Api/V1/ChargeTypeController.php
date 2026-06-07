<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreChargeTypeRequest;
use App\Http\Requests\Api\V1\UpdateChargeTypeRequest;
use App\Http\Resources\Api\V1\ChargeTypeResource;
use App\Models\ChargeType;
use App\Services\ChargeTypeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ChargeTypeController extends Controller
{
    use AuthorizesRequests;
    protected ChargeTypeService $service;

    /**
     * Inject single-purpose structural business services.
     */
    public function __construct(ChargeTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a multi-tenant-scoped, paginated record collection.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ChargeType::class);

        $query = ChargeType::query()
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%");
            });
        }

        return ChargeTypeResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * Route, intercept, validate, and write a new context record.
     */
    public function store(StoreChargeTypeRequest $request): ChargeTypeResource
    {
        $this->authorize('create', ChargeType::class);

        $chargeType = $this->service->create(
            $request->validated(),
            $request->user()->company_id,
            $request->user()->id
        );

        return new ChargeTypeResource($chargeType);
    }

    /**
     * Map clean singular object details.
     */
    public function show(ChargeType $chargeType): ChargeTypeResource
    {
        $this->authorize('view', $chargeType);

        return new ChargeTypeResource($chargeType);
    }

    /**
     * Intercept validation transformations to process variations safely.
     */
    public function update(UpdateChargeTypeRequest $request, ChargeType $chargeType): ChargeTypeResource
    {
        $this->authorize('update', $chargeType);

        $updatedChargeType = $this->service->update(
            $chargeType,
            $request->validated(),
            $request->user()->id
        );

        return new ChargeTypeResource($updatedChargeType);
    }

    /**
     * Evaluate and dispatch deletion executions.
     */
    public function destroy(ChargeType $chargeType): Response
    {
        $this->authorize('delete', $chargeType);

        $this->service->delete($chargeType);

        return response()->noContent();
    }
}