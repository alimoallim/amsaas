<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendCollectionReminderRequest;
use App\Models\CollectionNotice;
use App\Models\DelinquencyFlag;
use App\Services\Collections\AgingReceivablesService;
use App\Services\Collections\CollectionNoticePdfService;
use App\Services\Collections\CollectionReminderService;
use App\Services\Collections\DelinquencyTrackingService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function aging(Request $request, AgingReceivablesService $aging): JsonResponse
    {
        $validated = $request->validate([
            'as_of' => 'nullable|date',
            'building_id' => 'nullable|uuid|exists:buildings,id',
            'group_by' => 'nullable|string|in:tenant,building,invoice',
        ]);

        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $asOf = isset($validated['as_of'])
            ? Carbon::parse($validated['as_of'])->startOfDay()
            : now()->startOfDay();

        $data = $aging->report(
            $user,
            $asOf,
            $validated['building_id'] ?? null,
            $validated['group_by'] ?? 'tenant',
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function delinquency(Request $request, DelinquencyTrackingService $tracking): JsonResponse
    {
        $validated = $request->validate([
            'as_of' => 'nullable|date',
            'building_id' => 'nullable|uuid|exists:buildings,id',
            'escalation_stage' => 'nullable|string|in:first_notice,second_notice,legal_handoff',
        ]);

        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $asOf = isset($validated['as_of'])
            ? Carbon::parse($validated['as_of'])->startOfDay()
            : now()->startOfDay();

        $data = $tracking->listForCompany(
            $user,
            $validated['escalation_stage'] ?? null,
            $validated['building_id'] ?? null,
            $asOf,
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function sendReminders(
        SendCollectionReminderRequest $request,
        CollectionReminderService $reminders,
    ): JsonResponse {
        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $stats = $reminders->queueManualReminders($user, $request->validated('flag_ids'));

        return response()->json([
            'success' => true,
            'message' => sprintf(
                '%d reminder(s) queued, %d skipped.',
                $stats['queued'],
                $stats['skipped'],
            ),
            'data' => $stats,
        ]);
    }

    public function generateNotice(
        Request $request,
        CollectionNoticePdfService $notices,
    ): JsonResponse {
        $validated = $request->validate([
            'flag_id' => 'required|uuid|exists:delinquency_flags,id',
        ]);

        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $flag = DelinquencyFlag::query()
            ->where('company_id', $user->company_id)
            ->whereNull('resolved_at')
            ->with('monthlyInvoice')
            ->findOrFail($validated['flag_id']);

        $notice = $notices->generateForFlag($flag, $user);

        return response()->json([
            'success' => true,
            'message' => 'Collection notice generated.',
            'data' => [
                'id' => $notice->id,
                'notice_type' => $notice->notice_type?->value,
                'notice_label' => $notice->notice_type?->label(),
                'invoice_number' => $flag->monthlyInvoice?->invoice_number,
            ],
        ]);
    }

    public function downloadNotice(Request $request, CollectionNotice $notice)
    {
        abort_unless($notice->company_id === $request->user()->company_id, 404);

        if (! Storage::disk('local')->exists($notice->file_path)) {
            return response()->json(['message' => 'Notice file not found. Generate it again.'], 404);
        }

        $filename = sprintf(
            'collection-notice-%s.pdf',
            $notice->notice_type?->value ?? 'notice',
        );

        return response()->download(Storage::disk('local')->path($notice->file_path), $filename);
    }

    public function reminderLogs(Request $request, CollectionReminderService $reminders): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid|exists:tenants,id',
        ]);

        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        return response()->json([
            'success' => true,
            'data' => $reminders->logsForTenant($user, $validated['tenant_id']),
        ]);
    }

    public function agingExport(Request $request, AgingReceivablesService $aging): StreamedResponse
    {
        $validated = $request->validate([
            'as_of' => 'nullable|date',
            'building_id' => 'nullable|uuid|exists:buildings,id',
        ]);

        $user = $request->user();
        TenantContext::setCompanyId((string) $user->company_id);

        $asOf = isset($validated['as_of'])
            ? Carbon::parse($validated['as_of'])->startOfDay()
            : now()->startOfDay();

        $rows = $aging->exportRows(
            $user,
            $asOf,
            $validated['building_id'] ?? null,
        );

        $filename = 'aging-receivables-'.$asOf->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice',
                'Tenant',
                'Building',
                'Due date',
                'Days overdue',
                'Bucket',
                'Balance due',
                'Status',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['invoice_number'] ?? '',
                    $row['tenant']['display_name'] ?? '',
                    $row['building']['name'] ?? '',
                    $row['due_date'] ?? '',
                    $row['days_overdue'] ?? 0,
                    $row['bucket'] ?? '',
                    $row['balance_due'] ?? 0,
                    $row['status'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
