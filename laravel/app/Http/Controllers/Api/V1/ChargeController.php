<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ChargeResource;
use App\Models\Charge;
use App\Services\Billing\ChargeWorkflowService;
use App\Support\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Charge::class);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $query = Charge::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['chargeType', 'chargeModel', 'meterReading'])
            ->latest('charged_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('meter_reading_id')) {
            $query->where('meter_reading_id', $request->meter_reading_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('charge_number', 'ILIKE', "%{$search}%")
                    ->orWhere('tenant_name_snapshot', 'ILIKE', "%{$search}%")
                    ->orWhere('apartment_label_snapshot', 'ILIKE', "%{$search}%")
                    ->orWhere('building_name_snapshot', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = min(100, max(10, $request->integer('per_page', 20)));
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Charges retrieved successfully.',
            'data' => ChargeResource::collection($paginated->items())->resolve(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, Charge $charge): JsonResponse
    {
        $this->authorize('view', $charge);

        abort_unless(
            $charge->company_id === $request->user()->company_id,
            404
        );

        $charge->load(['chargeType', 'chargeModel', 'meterReading']);

        return response()->json([
            'success' => true,
            'message' => 'Charge retrieved successfully.',
            'data' => new ChargeResource($charge),
        ]);
    }

    public function approve(Request $request, Charge $charge, ChargeWorkflowService $workflow): JsonResponse
    {
        $this->authorize('approve', $charge);

        abort_unless(
            $charge->company_id === $request->user()->company_id,
            404
        );

        $charge = $workflow->approve($charge, $request->user());
        $charge->load(['chargeType', 'chargeModel', 'meterReading', 'invoice']);

        return response()->json([
            'success' => true,
            'message' => $this->approveSuccessMessage($charge),
            'data' => new ChargeResource($charge),
        ]);
    }

    public function reject(Request $request, Charge $charge, ChargeWorkflowService $workflow): JsonResponse
    {
        $this->authorize('reject', $charge);

        abort_unless(
            $charge->company_id === $request->user()->company_id,
            404
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $charge = $workflow->reject($charge, $request->user(), $validated['reason']);
        $charge->load(['chargeType', 'chargeModel', 'meterReading']);

        return response()->json([
            'success' => true,
            'message' => 'Charge rejected successfully.',
            'data' => new ChargeResource($charge),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Charge::class);

        $companyId = $request->user()->company_id;
        TenantContext::setCompanyId((string) $companyId);

        $base = Charge::query()
            ->where('company_id', $companyId)
            ->where('category', Charge::CATEGORY_UTILITY);

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => (clone $base)->where('status', Charge::STATUS_PENDING)->whereNull('invoice_id')->count(),
                'approved_ready' => (clone $base)->where('status', Charge::STATUS_APPROVED)->whereNull('invoice_id')->count(),
                'invoiced' => (clone $base)->where('status', Charge::STATUS_INVOICED)->count(),
                'cancelled' => (clone $base)->where('status', Charge::STATUS_CANCELLED)->count(),
            ],
        ]);
    }

    public function bulkApprove(Request $request, ChargeWorkflowService $workflow): JsonResponse
    {
        $this->authorize('viewAny', Charge::class);

        $validated = $request->validate([
            'charge_ids' => ['required', 'array', 'min:1'],
            'charge_ids.*' => ['required', 'uuid'],
        ]);

        $result = $workflow->bulkApprove($validated['charge_ids'], $request->user());

        $message = "Approved {$result['approved']} charge(s).";
        if (($result['synced_to_invoice'] ?? 0) > 0) {
            $invoices = implode(', ', $result['invoice_numbers'] ?? []);
            $message .= " {$result['synced_to_invoice']} added to invoice(s): {$invoices}.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result,
        ]);
    }

    protected function approveSuccessMessage(Charge $charge): string
    {
        if ($charge->status === Charge::STATUS_INVOICED && $charge->invoice) {
            if ($charge->invoice->status === 'draft') {
                return sprintf(
                    'Charge approved and added to draft invoice %s. Issue the invoice to collect payment.',
                    $charge->invoice->invoice_number,
                );
            }

            return sprintf(
                'Charge approved and added to invoice %s.',
                $charge->invoice->invoice_number,
            );
        }

        if ($charge->category === Charge::CATEGORY_UTILITY && $charge->status === Charge::STATUS_APPROVED) {
            return 'Charge approved but could not attach to a monthly invoice for this billing period.';
        }

        return 'Charge approved successfully.';
    }
}
