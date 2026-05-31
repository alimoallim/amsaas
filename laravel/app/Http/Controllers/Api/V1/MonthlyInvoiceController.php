<?php



namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\MonthlyInvoice;
use App\Services\InvoiceGenerationService;
use App\Http\Resources\Api\V1\MonthlyInvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MonthlyInvoiceController extends Controller
{
    /**
     * Generate a new monthly invoice.
     */
    public function store(Request $request, InvoiceGenerationService $service): JsonResponse
    {
        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'billing_year' => 'required|integer',
            'billing_month' => 'required|integer|between:1,12',
        ]);

        $apartment = Apartment::findOrFail($validated['apartment_id']);

        // Authorization: Ensure the apartment belongs to the user's company
        abort_if($apartment->building->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        $invoice = $service->generateForApartment(
            $apartment, 
            $validated['billing_year'], 
            $validated['billing_month']
        );

        return response()->json([
            'message' => 'Invoice generated successfully.', 
            'data' => new MonthlyInvoiceResource($invoice->load('lineItems'))
        ], 201);
    }

    /**
     * Finalize a draft invoice.
     */
    public function finalize(Request $request, MonthlyInvoice $invoice): JsonResponse
    {
        // 1. Authorization: Ensure the invoice belongs to the user's company
        abort_if($invoice->company_id !== $request->user()->company_id, 403, 'Unauthorized access.');

        // 2. Business Logic: Check if it's actually a draft
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be finalized.'], 422);
        }

        // 3. Update the record
        $invoice->update([
            'status' => 'finalized',
            'finalized_by' => $request->user()->id,
            'finalized_at' => now(),
        ]);

        return response()->json([
            'message' => 'Invoice finalized successfully.',
            'data' => new MonthlyInvoiceResource($invoice->fresh())
        ]);
    }
}