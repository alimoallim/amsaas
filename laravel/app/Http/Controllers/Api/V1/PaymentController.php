<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecordPaymentRequest;
use App\Http\Requests\Api\V1\RefundPaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Services\Accounting\JournalEntryService;
use App\Services\Accounting\PostingRuleService;
use App\Services\PaymentRefundService;
use App\Services\PaymentService;
use App\Services\TenantOpenBalanceService;
use App\Support\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function receiptAccountOptions(Request $request, PostingRuleService $postingRules): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $companyId = (string) $request->user()->company_id;

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => $postingRules->receiptAccountOptions($companyId),
                'defaults_by_method' => [
                    'cash' => $postingRules->receiptAccountCode('cash'),
                    'bank_transfer' => $postingRules->receiptAccountCode('bank_transfer'),
                    'mobile_money' => $postingRules->receiptAccountCode('mobile_money'),
                    'cheque' => $postingRules->receiptAccountCode('cheque'),
                ],
            ],
        ]);
    }

    public function tenantBalance(Request $request, TenantOpenBalanceService $balances): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

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
        $this->authorize('viewAny', Payment::class);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));
        $payments = Payment::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['tenant', 'allocations.monthlyInvoice'])
            ->latest('payment_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments->items())->resolve(),
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

    public function show(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $payment->load(['tenant', 'buyer', 'agreement', 'allocations.monthlyInvoice', 'recordedBy', 'salePaymentAllocations']);
        $payment->setRelation(
            'journalEntries',
            app(JournalEntryService::class)->entriesForPayment($payment),
        );

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    public function store(RecordPaymentRequest $request, PaymentService $payments): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = $payments->recordPayment(
            $request->user(),
            $request->validated()
        );

        $payment->load(['tenant', 'agreement', 'allocations.monthlyInvoice', 'recordedBy', 'salePaymentAllocations']);
        $payment->setRelation(
            'journalEntries',
            app(JournalEntryService::class)->entriesForPayment($payment),
        );

        return response()->json([
            'success' => true,
            'message' => $payments->resultMessage($payment),
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function refund(
        RefundPaymentRequest $request,
        Payment $payment,
        PaymentRefundService $refunds,
    ): JsonResponse {
        $this->authorize('refund', $payment);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $validated = $request->validated();

        try {
            $refunded = $refunds->refund(
                $request->user(),
                $payment,
                (float) $validated['amount'],
                $validated['reason'],
                $validated['allocation_ids'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $refunded->setRelation(
            'journalEntries',
            app(JournalEntryService::class)->entriesForPayment($refunded),
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment refund recorded and journal reversal posted.',
            'data' => new PaymentResource($refunded),
        ]);
    }
}
