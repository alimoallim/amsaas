<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Services\Dashboard\DashboardSummaryService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function show(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Building::class);

        $validated = $request->validate([
            'as_of' => 'nullable|date',
        ]);

        $user = $request->user()->loadMissing('company');
        TenantContext::setCompanyId((string) $user->company_id);

        $asOf = isset($validated['as_of'])
            ? Carbon::parse($validated['as_of'])->startOfDay()
            : now()->startOfDay();

        $data = app(DashboardSummaryService::class, ['user' => $user])->summary($asOf);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
