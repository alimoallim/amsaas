<?php 
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkInvoiceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceBulkController extends Controller
{
    public function bulkGenerate(Request $request)
    {
        // 1. Validate the structure
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer',
            'apartments' => 'required|array',
            'apartments.*.id' => 'required|uuid|exists:apartments,id',
            'apartments.*.readings' => 'nullable|array',
        ]);

        // 2. Dispatch to Queue
        // We pass the data to a Job so the user can keep working
        ProcessBulkInvoiceJob::dispatch(
            (string) $request->user()->company_id,
            $validated['apartments'],
            (int) $validated['year'],
            (int) $validated['month'],
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => 'Batch processing started. Invoices will be generated in the background.',
            'status' => 'queued'
        ], 202);
    }
}