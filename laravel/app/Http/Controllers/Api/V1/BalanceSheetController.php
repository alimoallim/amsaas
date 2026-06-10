<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\BalanceSheetService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceSheetController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected BalanceSheetService $balanceSheet,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'as_of' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $asOf = isset($validated['as_of']) ? Carbon::parse($validated['as_of']) : null;

        return response()->json([
            'success' => true,
            'data' => $this->balanceSheet->report($request->user(), $asOf),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'as_of' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $asOf = isset($validated['as_of']) ? Carbon::parse($validated['as_of']) : null;
        $report = $this->balanceSheet->report($request->user(), $asOf);
        $filename = sprintf('balance-sheet-%s.csv', $report['as_of']);

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Code', 'Account', 'Balance']);

            foreach ($report['sections']['assets']['rows'] as $row) {
                fputcsv($handle, ['Assets', $row['code'], $row['name'], $row['balance']]);
            }
            fputcsv($handle, ['Assets', 'TOTAL', '', $report['sections']['assets']['total']]);
            fputcsv($handle, []);

            foreach ($report['sections']['liabilities']['rows'] as $row) {
                fputcsv($handle, ['Liabilities', $row['code'], $row['name'], $row['balance']]);
            }
            fputcsv($handle, ['Liabilities', 'TOTAL', '', $report['sections']['liabilities']['total']]);
            fputcsv($handle, []);

            foreach ($report['sections']['equity']['rows'] as $row) {
                fputcsv($handle, ['Equity', $row['code'], $row['name'], $row['balance']]);
            }
            fputcsv($handle, ['Equity', 'TOTAL', '', $report['sections']['equity']['total']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Liabilities + Equity', '', '', $report['totals']['liabilities_and_equity']]);
            fputcsv($handle, ['Variance', '', '', $report['totals']['variance']]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportPdf(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'as_of' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $asOf = isset($validated['as_of']) ? Carbon::parse($validated['as_of']) : null;
        $report = $this->balanceSheet->report($request->user(), $asOf);
        $binary = $this->balanceSheet->pdfBinary($request->user(), $asOf);

        if ($binary === null || $binary === '') {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate balance sheet PDF. Ensure DomPDF is installed.',
            ], 422);
        }

        $filename = sprintf('balance-sheet-%s.pdf', $report['as_of']);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }
}
