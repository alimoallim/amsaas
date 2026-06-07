<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecordPaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\TenantOpenBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function tenantBalance(Request $request, TenantOpenBalanceService $balances): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid|exists:tenants,id',
            'building_id' => 'nullable|uuid|exists:buildings,id',
            'year' => 'nullable|integer|between:2020,2050',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $data = $balances->forTenant(
            $request->user(),
            $validated['tenant_id'],
            $validated['building_id'] ?? null,
            isset($validated['year']) ? (int) $validated['year'] : null,
            isset($validated['month']) ? (int) $validated['month'] : null,
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['tenant', 'allocations.monthlyInvoice'])
            ->latest('payment_date')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ],
        ]);
    }

    public function store(RecordPaymentRequest $request, PaymentService $payments): JsonResponse
    {
        $payment = $payments->recordPayment(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => $payments->resultMessage($payment),
            'data' => new PaymentResource($payment),
        ], 201);
    }
}
