<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApartmentInventoryStatusLogResource;
use App\Http\Resources\Api\V1\ApartmentResource;
use App\Models\Apartment;
use App\Models\ApartmentInventoryStatusLog;
use App\Services\Property\ApartmentInventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    public function available(Request $request): AnonymousResourceCollection
    {
        $query = Apartment::query()
            ->with(['building', 'activeLease'])
            ->sale()
            ->where('company_id', $request->user()->company_id)
            ->whereIn('inventory_status', [
                Apartment::STATUS_AVAILABLE,
                Apartment::STATUS_RESERVED,
            ]);

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->integer('floor'));
        }

        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', '>=', $request->integer('bedrooms'));
        }

        if ($request->filled('min_price')) {
            $query->where('market_sale_price', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('market_sale_price', '<=', $request->input('max_price'));
        }

        if ($request->filled('inventory_status')) {
            $query->where('inventory_status', $request->inventory_status);
        }

        if ($request->boolean('sellable_only')) {
            $query->whereDoesntHave('agreements', function ($agreementQuery) {
                $agreementQuery->whereIn('status', ApartmentInventoryService::LEASE_BLOCKING_STATUSES);
            })->where('inventory_status', '!=', Apartment::STATUS_OCCUPIED);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($sub) use ($search) {
                $sub->where('unit_number', 'ilike', "%{$search}%")
                    ->orWhere('property_type', 'ilike', "%{$search}%");
            });
        }

        $sortBy = in_array($request->get('sort_by'), [
            'unit_number', 'floor', 'bedrooms', 'market_sale_price', 'created_at',
        ], true) ? $request->get('sort_by') : 'market_sale_price';

        $sortDirection = $request->get('sort_direction') === 'desc' ? 'desc' : 'asc';

        $apartments = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min($request->integer('per_page', 15), 100));

        return ApartmentResource::collection($apartments);
    }

    public function history(Request $request, Apartment $apartment): AnonymousResourceCollection
    {
        abort_if($apartment->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        $logs = ApartmentInventoryStatusLog::query()
            ->where('apartment_id', $apartment->id)
            ->where('company_id', $request->user()->company_id)
            ->with('changedByUser')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApartmentInventoryStatusLogResource::collection($logs);
    }
}
