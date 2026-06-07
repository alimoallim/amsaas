<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BulkIssueInvoicesRequest;
use App\Http\Requests\Api\V1\MonthlyInvoiceIndexRequest;
use App\Http\Requests\Api\V1\StoreMonthlyInvoiceRequest;
use App\Http\Resources\Api\V1\MonthlyInvoiceResource;
use App\Models\Apartment;
use App\Models\MonthlyInvoice;
use App\Services\Billing\BillingPipelineService;
use App\Services\Billing\InvoiceVoidService;
use App\Services\Billing\ManualInvoiceService;
use App\Services\Billing\MonthlyInvoiceListService;
use App\Services\InvoiceGenerationService;
use App\Services\InvoiceService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MonthlyInvoiceController extends Controller
{
    public function index(MonthlyInvoiceIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $service = app(MonthlyInvoiceListService::class, ['user' => $user]);
        $paginator = $service->paginate($request->validated());

        $agreements = $service->agreementsForInvoices($paginator->items());
        foreach ($paginator->items() as $invoice) {
            $agreement = $agreements->get($invoice->contract_id);
            if ($agreement) {
                $invoice->setRelation('resolvedAgreement', $agreement);
            }
        }

        return response()->json([
            'success' => true,
            'data' => MonthlyInvoiceResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|between:2020,2050',
            'month' => 'required|integer|between:1,12',
        ]);

        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $service = app(MonthlyInvoiceListService::class, ['user' => $user]);
        $data = $service->summary((int) $validated['year'], (int) $validated['month']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function bulkIssue(BulkIssueInvoicesRequest $request): JsonResponse
    {
        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $validated = $request->validated();
        $service = app(MonthlyInvoiceListService::class, ['user' => $user]);

        $ids = isset($validated['ids']) ? $validated['ids'] : null;
        $result = $service->bulkIssue(
            (int) $validated['year'],
            (int) $validated['month'],
            $ids
        );

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Issued %d invoice(s). %d failed, %d skipped.',
                $result['issued'],
                $result['failed'],
                $result['skipped']
            ),
            'data' => $result,
        ]);
    }

    public function show(Request $request, MonthlyInvoice $invoice): JsonResponse
    {
        abort_if($invoice->company_id !== $request->user()->company_id, 404);

        $invoice->load([
            'lineItems',
            'apartment.building',
            'allocations.payment',
        ]);
        $agreement = app(MonthlyInvoiceListService::class, ['user' => $request->user()])
            ->agreementsForInvoices([$invoice])
            ->get($invoice->contract_id);
        if ($agreement) {
            $invoice->setRelation('resolvedAgreement', $agreement);
        }

        return response()->json([
            'success' => true,
            'data' => new MonthlyInvoiceResource($invoice),
        ]);
    }

    public function download(Request $request, string $id)
    {
        $invoice = MonthlyInvoice::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        if (! $invoice->file_path) {
            return response()->json(['message' => 'Invoice file path is not set.'], 404);
        }

        if (! Storage::disk('local')->exists($invoice->file_path)) {
            return response()->json(['message' => 'The invoice file has not been generated or does not exist.'], 404);
        }

        return response()->download(storage_path('app/'.$invoice->file_path));
    }

    public function bulkMarkPaid(Request $request, InvoiceService $invoiceService): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:monthly_invoices,id',
        ]);

        $invoices = MonthlyInvoice::query()
            ->where('company_id', $request->user()->company_id)
            ->whereIn('id', $validated['ids'])
            ->get();

        $updated = 0;
        foreach ($invoices as $invoice) {
            if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
                continue;
            }
            $balance = (float) $invoice->balance_due;
            if ($balance > 0) {
                $invoiceService->applyPayment($invoice, $balance);
            } else {
                $invoice->update(['status' => 'paid']);
            }
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected invoices marked as paid.',
            'updated' => $updated,
        ]);
    }

    /**
     * Create a draft invoice (manual line items or auto-rent shortcut).
     */
    public function store(
        StoreMonthlyInvoiceRequest $request,
        ManualInvoiceService $manualInvoices,
        InvoiceGenerationService $autoGenerator,
    ): JsonResponse {
        TenantContext::setCompanyId((string) $request->user()->company_id);

        $validated = $request->validated();

        try {
            if (! empty($validated['line_items'])) {
                $invoice = $manualInvoices->create($request->user(), $validated);
                $message = 'Manual invoice created successfully.';
            } else {
                $apartment = Apartment::with(['building', 'activeLease.rentalAgreement'])
                    ->findOrFail($validated['apartment_id']);

                abort_if(
                    $apartment->building->company_id !== $request->user()->company_id,
                    403,
                    'Unauthorized access.'
                );

                $invoice = $autoGenerator->generateForApartment(
                    $apartment,
                    (int) $validated['billing_year'],
                    (int) $validated['billing_month']
                );
                $message = 'Invoice generated from rental agreement.';
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $agreement = app(MonthlyInvoiceListService::class, ['user' => $request->user()])
            ->agreementsForInvoices([$invoice])
            ->get($invoice->contract_id);
        if ($agreement) {
            $invoice->setRelation('resolvedAgreement', $agreement);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new MonthlyInvoiceResource($invoice->loadMissing('lineItems')),
        ], 201);
    }

    /**
     * Void an issued or draft invoice (audit trail preserved).
     */
    public function void(
        Request $request,
        MonthlyInvoice $invoice,
        InvoiceVoidService $voidService,
    ): JsonResponse {
        abort_if($invoice->company_id !== $request->user()->company_id, 404);

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:2000',
        ]);

        try {
            $voided = $voidService->void($invoice, $request->user(), $validated['reason']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $agreement = app(MonthlyInvoiceListService::class, ['user' => $request->user()])
            ->agreementsForInvoices([$voided])
            ->get($voided->contract_id);
        if ($agreement) {
            $voided->setRelation('resolvedAgreement', $agreement);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice voided. Source charges released for re-consolidation.',
            'data' => new MonthlyInvoiceResource($voided),
        ]);
    }

    /**
     * Finalize a draft invoice.
     */
    public function finalize(Request $request, MonthlyInvoice $invoice): JsonResponse
    {
        abort_if($invoice->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be issued.'], 422);
        }

        $pipeline = app(BillingPipelineService::class, ['user' => $request->user()]);
        $issued = $pipeline->issueInvoice($invoice);

        $issued->update([
            'finalized_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Invoice issued successfully. PDF generation has been queued.',
            'data' => new MonthlyInvoiceResource($issued->fresh(['apartment.building'])),
        ]);
    }
}
