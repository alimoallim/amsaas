<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecordReservationDepositRequest;
use App\Http\Requests\Api\V1\StoreSaleReservationRequest;
use App\Http\Resources\Api\V1\SaleReservationResource;
use App\Models\SaleReservation;
use App\Services\Sales\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = SaleReservation::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['apartment.building', 'buyer', 'depositPayment'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('apartment_id'), fn ($q) => $q->where('apartment_id', $request->apartment_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('reservation_number', 'ilike', "%{$search}%")
                        ->orWhereHas('buyer', fn ($b) => $b->where('full_name', 'ilike', "%{$search}%"))
                        ->orWhereHas('apartment', fn ($a) => $a->where('unit_number', 'ilike', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return SaleReservationResource::collection($reservations);
    }

    public function store(StoreSaleReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservations->create(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => $reservation->status === SaleReservation::STATUS_CONFIRMED
                ? 'Reservation created and deposit recorded.'
                : 'Reservation created. Collect deposit before expiry.',
            'data' => new SaleReservationResource($reservation),
        ], 201);
    }

    public function show(Request $request, SaleReservation $saleReservation): SaleReservationResource
    {
        abort_if($saleReservation->company_id !== $request->user()->company_id, 403);

        $saleReservation->load(['apartment.building', 'buyer', 'depositPayment']);

        return new SaleReservationResource($saleReservation);
    }

    public function recordDeposit(
        RecordReservationDepositRequest $request,
        SaleReservation $saleReservation,
    ): JsonResponse {
        abort_if($saleReservation->company_id !== $request->user()->company_id, 403);

        $reservation = $this->reservations->recordDeposit(
            $request->user(),
            $saleReservation,
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Deposit recorded and reservation confirmed.',
            'data' => new SaleReservationResource($reservation),
        ]);
    }

    public function cancel(Request $request, SaleReservation $saleReservation): JsonResponse
    {
        abort_if($saleReservation->company_id !== $request->user()->company_id, 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation = $this->reservations->cancel(
            $request->user(),
            $saleReservation,
            $validated['reason'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled and unit released.',
            'data' => new SaleReservationResource($reservation),
        ]);
    }
}
