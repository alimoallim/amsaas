<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAccountRequest;
use App\Http\Requests\Api\V1\UpdateAccountRequest;
use App\Http\Resources\Api\V1\AccountResource;
use App\Models\Account;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\GeneralLedgerService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ChartOfAccountsService $chartOfAccounts,
        protected GeneralLedgerService $generalLedger,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Account::class);

        $query = Account::query()
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%");
            });
        }

        return AccountResource::collection(
            $query->paginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreAccountRequest $request): AccountResource
    {
        $this->authorize('create', Account::class);

        $account = $this->chartOfAccounts->create(
            $request->validated(),
            $request->user()->company_id,
            $request->user()->id,
        );

        return new AccountResource($account);
    }

    public function show(Account $account): AccountResource
    {
        $this->authorize('view', $account);

        return new AccountResource($account);
    }

    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $this->authorize('update', $account);

        $updated = $this->chartOfAccounts->update(
            $account,
            $request->validated(),
            $request->user()->id,
        );

        return new AccountResource($updated);
    }

    public function ledger(Request $request, Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        return response()->json([
            'success' => true,
            'data' => $this->generalLedger->ledger($request->user(), $account, $from, $to),
        ]);
    }

    public function ledgerExport(Request $request, Account $account): StreamedResponse
    {
        $this->authorize('view', $account);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : null;

        $rows = $this->generalLedger->exportRows($request->user(), $account, $from, $to);
        $fromLabel = ($from ?? now()->startOfMonth())->format('Y-m-d');
        $toLabel = ($to ?? now())->format('Y-m-d');
        $filename = sprintf('ledger-%s-%s-%s.csv', $account->code, $fromLabel, $toLabel);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Entry #', 'Description', 'Debit', 'Credit', 'Running balance']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['entry_date'] ?? '',
                    $row['entry_number'] ?? '',
                    $row['description'] ?? '',
                    $row['debit_amount'] ?? '',
                    $row['credit_amount'] ?? '',
                    $row['running_balance'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function destroy(Account $account): Response|JsonResponse
    {
        $this->authorize('delete', $account);

        try {
            $this->chartOfAccounts->delete($account);
        } catch (BusinessRuleException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
            ], 422);
        }

        return response()->noContent();
    }
}
