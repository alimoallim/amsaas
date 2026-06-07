<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTenantRequest;
use App\Http\Requests\Api\V1\UpdateTenantRequest;
use App\Http\Resources\Api\V1\TenantResource;
use App\Models\Agreement;
use App\Models\Tenant;
use App\Services\Property\TenantLeaseEligibilityService;
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