<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBuyerRequest;
use App\Http\Requests\Api\V1\UpdateBuyerRequest;
use App\Http\Resources\Api\V1\BuyerResource;
use App\Models\Agreement;
use App\Models\Buyer;
use App\Services\Property\BuyerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BuyerController extends Controller
{
    public function __construct(
        private readonly BuyerProfileService $buyerProfiles,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $buyers = Buyer::query()
            ->where('company_id', $request->user()->company_id)
            ->with('tenant')
            ->withCount([
                'payments',
                'agreements as open_sale_agreements_count' => fn ($q) => $q
                    ->where('agreement_type', Agreement::TYPE_SALE)
                    ->whereNotIn('status', [
                        Agreement::STATUS_CANCELLED,
                        Agreement::STATUS_COMPLETED,
                    ]),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'ilike', "%{$search}%")
                        ->orWhere('buyer_code', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('national_id', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where(
                'is_active',
                filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
            ))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return BuyerResource::collection($buyers);
    }

    public function store(StoreBuyerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['company_id'] = $request->user()->company_id;
        $validated['buyer_code'] = $this->generateBuyerCode();

        $this->buyerProfiles->assertTenantLinkValid(
            new Buyer,
            $validated['tenant_id'] ?? null,
            (string) $request->user()->company_id,
        );

        $buyer = Buyer::create($validated);
        $buyer->load('tenant');

        return response()->json([
            'success' => true,
            'message' => 'Buyer created successfully.',
            'data' => new BuyerResource($buyer),
        ], 201);
    }

    public function show(Request $request, Buyer $buyer): BuyerResource
    {
        abort_if($buyer->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        $buyer->load('tenant');
        $buyer->loadCount([
            'payments',
            'agreements as open_sale_agreements_count' => fn ($q) => $q
                ->where('agreement_type', Agreement::TYPE_SALE)
                ->whereNotIn('status', [
                    Agreement::STATUS_CANCELLED,
                    Agreement::STATUS_COMPLETED,
                ]),
        ]);

        return new BuyerResource($buyer);
    }

    public function update(UpdateBuyerRequest $request, Buyer $buyer): JsonResponse
    {
        abort_if($buyer->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        $validated = $request->validated();

        if (array_key_exists('tenant_id', $validated)) {
            $this->buyerProfiles->assertTenantLinkValid(
                $buyer,
                $validated['tenant_id'],
                (string) $request->user()->company_id,
            );
        }

        $buyer->update($validated);
        $buyer->load('tenant');

        return response()->json([
            'success' => true,
            'message' => 'Buyer updated successfully.',
            'data' => new BuyerResource($buyer),
        ]);
    }

    public function destroy(Request $request, Buyer $buyer): JsonResponse
    {
        abort_if($buyer->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        $this->buyerProfiles->assertCanDelete($buyer);
        $buyer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Buyer deleted successfully.',
        ]);
    }

    protected function generateBuyerCode(): string
    {
        return 'BUY-'.strtoupper(Str::random(8));
    }
}
