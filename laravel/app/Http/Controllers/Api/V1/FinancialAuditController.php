<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\FinancialAuditService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialAuditController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected FinancialAuditService $financialAudit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'entity_type' => 'nullable|string|in:'.implode(',', array_keys(FinancialAuditService::ENTITY_MAP)),
            'action' => 'nullable|string|in:created,updated,deleted,posted',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        $result = $this->financialAudit->timeline(
            $request->user(),
            $from,
            $to,
            $validated['entity_type'] ?? null,
            $validated['action'] ?? null,
            (int) ($validated['per_page'] ?? 25),
            (int) ($validated['page'] ?? 1),
        );

        return response()->json([
            'success' => true,
            'data' => $result['rows'],
            'meta' => $result['meta'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Account::class);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'entity_type' => 'nullable|string|in:'.implode(',', array_keys(FinancialAuditService::ENTITY_MAP)),
            'action' => 'nullable|string|in:created,updated,deleted,posted',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        $rows = $this->financialAudit->exportRows(
            $request->user(),
            $from,
            $to,
            $validated['entity_type'] ?? null,
            $validated['action'] ?? null,
        );

        $fromLabel = ($from ?? now()->subDays(30))->format('Y-m-d');
        $toLabel = ($to ?? now())->format('Y-m-d');

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Occurred at', 'Action', 'Entity', 'Entity ID', 'Summary', 'User', 'Source']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['occurred_at'] ?? '',
                    $row['action'] ?? '',
                    $row['entity_type'] ?? '',
                    $row['entity_id'] ?? '',
                    $row['summary'] ?? '',
                    $row['user']['name'] ?? '',
                    $row['source'] ?? '',
                ]);
            }

            fclose($handle);
        }, sprintf('financial-audit-%s-%s.csv', $fromLabel, $toLabel), [
            'Content-Type' => 'text/csv',
        ]);
    }
}
