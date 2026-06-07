<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingCloseReadinessService;
use App\Services\Billing\BillingPipelineService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingOperationsController extends Controller
{
    /**
     * Pipeline snapshot: counts at each stage for the authenticated company.
     */
    public function pipelineStatus(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|between:2020,2050',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $user = Auth::user();
        TenantContext::setCompanyId((string) $user->company_id);

        $billingDate = Carbon::create(
            (int) ($request->year ?? now()->year),
            (int) ($request->month ?? now()->month),
            1
        )->startOfMonth();

        $pipeline = app(BillingPipelineService::class, ['user' => $user]);

        return response()->json([
            'success' => true,
            'data' => $pipeline->status($billingDate),
        ]);
    }

    /**
     * Fetch unposted staging metrics for the authenticated company.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|between:2020,2050',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $companyId = Auth::user()->company_id;
        TenantContext::setCompanyId((string) $companyId);

        $billingDate = Carbon::create(
            (int) ($request->year ?? now()->year),
            (int) ($request->month ?? now()->month),
            1
        )->startOfMonth();

        $readiness = app(BillingCloseReadinessService::class, ['user' => Auth::user()]);
        $metrics = $readiness->metricsForPeriod($billingDate);
        $pipeline = app(BillingPipelineService::class, ['user' => Auth::user()])->status($billingDate);

        return response()->json([
            'company_id' => $companyId,
            'period_display' => $billingDate->format('F Y'),
            'metrics' => $metrics,
            'pipeline' => $pipeline,
        ]);
    }

    /**
     * Run the monthly billing close: recurring items + invoice consolidation.
     */
    public function triggerConsolidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|between:2020,2050',
            'month' => 'required|integer|between:1,12',
            'generate_recurring' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        TenantContext::setCompanyId((string) $user->company_id);

        $billingDate = Carbon::create((int) $validated['year'], (int) $validated['month'], 1)->startOfMonth();

        $pipeline = app(BillingPipelineService::class, ['user' => $user]);
        $result = $pipeline->runMonthlyClose(
            $billingDate,
            $request->boolean('generate_recurring', true)
        );

        $status = $result['pipeline']['blocking_pending_utility_charges'] > 0
            ? 'completed_with_warnings'
            : 'completed';

        return response()->json([
            'status' => $status,
            'period' => $result['period'],
            'billing_run' => $result['billing_run'],
            'results' => $result['consolidation'],
            'pipeline' => $result['pipeline'],
            'message' => $status === 'completed_with_warnings'
                ? 'Invoices compiled. Some utility charges still await approval and were excluded.'
                : 'Monthly billing close completed.',
        ]);
    }
}
