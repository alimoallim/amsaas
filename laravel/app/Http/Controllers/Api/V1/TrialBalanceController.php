<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\TrialBalanceService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TrialBalanceService $trialBalance,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        return response()->json([
            'success' => true,
            'data' => $this->trialBalance->report($request->user(), $from, $to),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        $report = $this->trialBalance->report($request->user(), $from, $to);
        $fromLabel = $report['period']['from'];
        $toLabel = $report['period']['to'];
        $filename = sprintf('trial-balance-%s-%s.csv', $fromLabel, $toLabel);

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Account', 'Type', 'Period debits', 'Period credits', 'Balance debit', 'Balance credit']);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['code'],
                    $row['name'],
                    $row['type'],
                    $row['period_debits'],
                    $row['period_credits'],
                    $row['balance_debit'],
                    $row['balance_credit'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                $report['totals']['activity_debit'],
                $report['totals']['activity_credit'],
                $report['totals']['balance_debit'],
                $report['totals']['balance_credit'],
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function closePeriod(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'fiscal_month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        try {
            $result = $this->trialBalance->closePeriod(
                $request->user(),
                (int) $validated['fiscal_year'],
                (int) $validated['fiscal_month'],
                $validated['notes'] ?? null,
            );
        } catch (BusinessRuleException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
