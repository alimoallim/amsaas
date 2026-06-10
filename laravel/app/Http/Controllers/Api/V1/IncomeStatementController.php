<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\IncomeStatementService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomeStatementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected IncomeStatementService $incomeStatement,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'billing_year' => 'nullable|integer|min:2000|max:2100',
            'billing_month' => 'nullable|integer|min:1|max:12',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;
        $billingYear = isset($validated['billing_year']) ? (int) $validated['billing_year'] : null;
        $billingMonth = isset($validated['billing_month']) ? (int) $validated['billing_month'] : null;

        if (($billingYear === null) xor ($billingMonth === null)) {
            return response()->json([
                'success' => false,
                'message' => 'Both billing_year and billing_month are required when filtering by billing period.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->incomeStatement->report(
                $request->user(),
                $from,
                $to,
                $billingYear,
                $billingMonth,
            ),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'billing_year' => 'nullable|integer|min:2000|max:2100',
            'billing_month' => 'nullable|integer|min:1|max:12',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;
        $billingYear = isset($validated['billing_year']) ? (int) $validated['billing_year'] : null;
        $billingMonth = isset($validated['billing_month']) ? (int) $validated['billing_month'] : null;

        $report = $this->incomeStatement->report(
            $request->user(),
            $from,
            $to,
            $billingYear,
            $billingMonth,
        );
        $filename = sprintf(
            'income-statement-%s-%s.csv',
            $report['period']['from'],
            $report['period']['to'],
        );

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Code', 'Account', 'Amount']);

            foreach ($report['sections']['revenue']['rows'] as $row) {
                fputcsv($handle, ['Revenue', $row['code'], $row['name'], $row['amount']]);
            }

            fputcsv($handle, ['Revenue', 'TOTAL', '', $report['sections']['revenue']['total']]);
            fputcsv($handle, []);

            foreach ($report['sections']['expenses']['rows'] as $row) {
                fputcsv($handle, ['Expense', $row['code'], $row['name'], $row['amount']]);
            }

            fputcsv($handle, ['Expense', 'TOTAL', '', $report['sections']['expenses']['total']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Net income', '', '', $report['totals']['net_income']]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportPdf(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'billing_year' => 'nullable|integer|min:2000|max:2100',
            'billing_month' => 'nullable|integer|min:1|max:12',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;
        $billingYear = isset($validated['billing_year']) ? (int) $validated['billing_year'] : null;
        $billingMonth = isset($validated['billing_month']) ? (int) $validated['billing_month'] : null;

        $report = $this->incomeStatement->report(
            $request->user(),
            $from,
            $to,
            $billingYear,
            $billingMonth,
        );
        $binary = $this->incomeStatement->pdfBinary(
            $request->user(),
            $from,
            $to,
            $billingYear,
            $billingMonth,
        );

        if ($binary === null || $binary === '') {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate income statement PDF. Ensure DomPDF is installed.',
            ], 422);
        }

        $filename = sprintf(
            'income-statement-%s-%s.pdf',
            $report['period']['from'],
            $report['period']['to'],
        );

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }
}
