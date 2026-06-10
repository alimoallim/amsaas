<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTenantRequest;
use App\Http\Requests\Api\V1\UpdateTenantRequest;
use App\Http\Resources\Api\V1\MonthlyInvoiceResource;
use App\Http\Resources\Api\V1\TenantResource;
use App\Models\Agreement;
use App\Models\Tenant;
use App\Services\Billing\MonthlyInvoiceListService;
use App\Services\Billing\TenantBillingService;
use App\Services\Property\TenantLeaseEligibilityService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantLeaseEligibilityService $leaseEligibility,
    ) {}
    /*
    |--------------------------------------------------------------------------
    | List Tenants
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): AnonymousResourceCollection {

        $tenants = Tenant::query()

            /*
            |--------------------------------------------------------------------------
            | Tenant Isolation
            |--------------------------------------------------------------------------
            */

            ->where(
                'company_id',
                $request->user()->company_id
            )

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->search,

                function (
                    $query,
                    $search
                ) {

                    $query->where(function ($subQuery) use ($search) {

                        $subQuery

                            ->where(
                                'display_name',
                                'ilike',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'tenant_code',
                                'ilike',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'email',
                                'ilike',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'phone',
                                'ilike',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'national_id',
                                'ilike',
                                "%{$search}%"
                            );
                    });
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Status Filter
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->status,

                function (
                    $query,
                    $status
                ) {

                    $query->where(
                        'status',
                        $status
                    );
                }
            )

            ->when(
                $request->filled('building_id'),
                function ($query) use ($request) {
                    $buildingId = $request->string('building_id');
                    $query->whereHas('agreements', function ($agreementQuery) use ($buildingId) {
                        $agreementQuery
                            ->where('status', Agreement::STATUS_ACTIVE)
                            ->whereHas('apartment', fn ($apartmentQuery) => $apartmentQuery
                                ->where('building_id', $buildingId));
                    });
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Tenant Type Filter
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->tenant_type,

                function (
                    $query,
                    $tenantType
                ) {

                    $query->where(
                        'tenant_type',
                        $tenantType
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            ->latest()

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            ->paginate(

                $request->integer(
                    'per_page',
                    15
                )
            );

        return TenantResource::collection(
            $tenants
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tenant
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreTenantRequest $request
    ): JsonResponse {

        $validated =
            $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        $validated['company_id'] =

            $request->user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Audit Fields
        |--------------------------------------------------------------------------
        */

        $validated['created_by'] =

            $request->user()->id;

        $validated['updated_by'] =

            $request->user()->id;

        /*
        |--------------------------------------------------------------------------
        | Generate Tenant Code
        |--------------------------------------------------------------------------
        */

        $validated['tenant_code'] =

            $this->generateTenantCode();

        /*
        |--------------------------------------------------------------------------
        | Create Tenant
        |--------------------------------------------------------------------------
        */

        $tenant = Tenant::create(
            $validated
        );

        return response()->json([

            'success' => true,

            'message' =>

                'Tenant created successfully.',

            'data' =>

                new TenantResource(
                    $tenant
                )

        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tenant
    |--------------------------------------------------------------------------
    */

    public function billing(
        Request $request,
        Tenant $tenant,
    ): JsonResponse {
        abort_if(
            $tenant->company_id !== $request->user()->company_id,
            403,
            'Unauthorized access.'
        );

        $validated = $request->validate([
            'year' => 'nullable|integer|between:2020,2050',
            'month' => 'nullable|integer|between:1,12',
            'status' => 'nullable|string|in:draft,issued,finalized,partially_paid,paid,overdue,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        TenantContext::setCompanyId((string) $request->user()->company_id);

        $billing = app(TenantBillingService::class, ['user' => $request->user()])
            ->billingHistory($tenant, $validated);

        $paginator = $billing['paginator'];
        $listService = app(MonthlyInvoiceListService::class, ['user' => $request->user()]);
        $agreements = $listService->agreementsForInvoices($paginator->items());

        foreach ($paginator->items() as $invoice) {
            $agreement = $agreements->get($invoice->contract_id);
            if ($agreement) {
                $invoice->setRelation('resolvedAgreement', $agreement);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => new TenantResource($tenant),
                'summary' => $billing['summary'],
                'invoices' => MonthlyInvoiceResource::collection($paginator),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(
        Request $request,
        Tenant $tenant
    ): TenantResource {

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        abort_if(

            $tenant->company_id
                !== $request->user()->company_id,

            403,

            'Unauthorized access.'
        );

        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        |
        | Keep lightweight for now.
        | Add more relationships gradually
        | as modules stabilize.
        |
        */

        $tenant->load([

            // 'rentalAgreements',
            // 'payments',
        ]);

        return new TenantResource(
            $tenant
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tenant
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateTenantRequest $request,
        Tenant $tenant
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Update Audit
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validated();

        $validated['updated_by'] =

            $request->user()->id;

        /*
        |--------------------------------------------------------------------------
        | Update Tenant
        |--------------------------------------------------------------------------
        */

        $tenant->update(
            $validated
        );

        return response()->json([

            'success' => true,

            'message' =>

                'Tenant updated successfully.',

            'data' =>

                new TenantResource(
                    $tenant
                )
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Tenant
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Tenant $tenant
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        abort_if(

            $tenant->company_id
                !== $request->user()->company_id,

            403,

            'Unauthorized access.'
        );

        $this->leaseEligibility->assertCanDelete($tenant);

        $tenant->delete();

        return response()->json([

            'success' => true,

            'message' =>

                'Tenant deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Tenant Code
    |--------------------------------------------------------------------------
    */

    protected function generateTenantCode(): string
    {
        return 'TNT-'
            . strtoupper(
                Str::random(8)
            );
    }
}