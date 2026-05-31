<?php

namespace App\Http\Controllers\Api\V1;

use Throwable;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\RentalAgreement;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Services\AgreementNumberService;

use App\Http\Controllers\Controller;

use App\Http\Resources\Api\V1\RentalAgreementResource;
  use App\Http\Requests\Api\V1\UpdateRentalAgreementRequest;


use App\Http\Requests\Api\V1\StoreRentalAgreementRequest;

class RentalAgreementController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Rental Agreements
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $agreements = RentalAgreement::query()

            ->with([

                'agreement.apartment',

                'agreement.tenant',
            ])

            ->whereHas(

                'agreement',

                fn ($query) =>

                $query->where(

                    'company_id',

                    $request->user()->company_id
                )
            )

            ->latest()

            ->paginate(

                $request->integer(
                    'per_page',
                    15
                )
            );

        return response()->json([

            'success' => true,

            'message' =>

                'Rental agreements retrieved successfully.',

            'data' =>

                RentalAgreementResource::collection(
                    $agreements
                ),

            'meta' => [

                'current_page' =>

                    $agreements->currentPage(),

                'last_page' =>

                    $agreements->lastPage(),

                'per_page' =>

                    $agreements->perPage(),

                'total' =>

                    $agreements->total(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Rental Agreement
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreRentalAgreementRequest $request
    ): JsonResponse {

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Lock Apartment Row
            |--------------------------------------------------------------------------
            */

            $apartment = Apartment::query()

                ->where(
                    'id',
                    $request->apartment_id
                )

                ->where(
                    'company_id',
                    $request->user()->company_id
                )

                ->lockForUpdate()

                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Prevent Concurrent Active Agreements
            |--------------------------------------------------------------------------
            */

            $activeAgreementExists = Agreement::query()

                ->where(
                    'apartment_id',
                    $apartment->id
                )

                ->where(
                    'status',
                    Agreement::STATUS_ACTIVE
                )

                ->exists();

            if ($activeAgreementExists) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' =>

                        'Apartment already has an active agreement.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Agreement Number
            |--------------------------------------------------------------------------
            */

            $agreementNumber =

                AgreementNumberService::generate(

                    Agreement::TYPE_RENTAL
                );

            /*
            |--------------------------------------------------------------------------
            | Create Base Agreement
            |--------------------------------------------------------------------------
            */

            $agreement = Agreement::create([

                'company_id' =>

                    $request->user()->company_id,

                'agreement_number' =>

                    $agreementNumber,

                'agreement_type' =>

                    Agreement::TYPE_RENTAL,

                'apartment_id' =>

                    $request->apartment_id,

                'tenant_id' =>

                    $request->tenant_id,

                'status' =>

                    $request->status
                    ?? Agreement::STATUS_DRAFT,

                'start_date' =>

                    $request->start_date,

                'end_date' =>

                    $request->end_date,

                'signed_at' =>

                    $request->signed_at,

                'contract_amount' =>

                    $request->contract_amount
                    ?? $request->monthly_rent,

                'currency' =>

                    $request->currency
                    ?? 'USD',

                'notes' =>

                    $request->notes,

                'created_by' =>

                    $request->user()->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Upload Contract File
            |--------------------------------------------------------------------------
            */

            $contractFilePath = null;

            if (

                $request->hasFile(
                    'contract_file'
                )

            ) {

                $contractFilePath =

                    $request->file(
                        'contract_file'
                    )->store(

                        'contracts/rental',

                        'public'
                    );

                $agreement->update([

                    'contract_file_path' =>

                        $contractFilePath,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Rental Agreement
            |--------------------------------------------------------------------------
            */

            $rentalAgreement =

                RentalAgreement::create([

                    'id' =>

                        $agreement->id,

                    'monthly_rent' =>

                        $request->monthly_rent,

                    'security_deposit' =>$request->security_deposit
                        ?? 0,

                    'payment_due_day' =>

                        $request->payment_due_day,

                    'includes_water' =>

                        $request->boolean(
                            'includes_water'
                        ),

                    'includes_electricity' =>

                        $request->boolean(
                            'includes_electricity'
                        ),

                    'includes_internet' =>

                        $request->boolean(
                            'includes_internet'
                        ),

                    'auto_renew' =>

                        $request->boolean(
                            'auto_renew'
                        ),

                    'renewal_notice_days' =>

                        $request->renewal_notice_days
                        ?? 30,

                    'special_terms' =>

                        $request->special_terms,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Occupancy Automation
            |--------------------------------------------------------------------------
            */

            if (

                $agreement->status
                === Agreement::STATUS_ACTIVE

            ) {

                $apartment->update([

                    'inventory_status' =>

                        Apartment::STATUS_OCCUPIED,
                ]);
            }

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Reload Relationships
            |--------------------------------------------------------------------------
            */

            $rentalAgreement->load([

                'agreement.apartment',

                'agreement.tenant',
            ]);

            return response()->json([

                'success' => true,

                'message' =>

                    'Rental agreement created successfully.',

                'data' =>

                    new RentalAgreementResource(

                        $rentalAgreement
                    ),
            ], 201);

        } catch (Throwable $exception) {

            DB::rollBack();

            report($exception);

           return response()->json([

    'success' => false,

    'message' =>

        'Failed to create rental agreement.',

    'exception' => [

        'message' =>

            $exception->getMessage(),

        'file' =>

            $exception->getFile(),

        'line' =>

            $exception->getLine(),

        'trace' =>

            app()->environment('local')
                ? explode(
                    "\n",
                    $exception->getTraceAsString()
                )
                : [],
    ],
], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Rental Agreement
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        string $id
    ): JsonResponse {

        $rentalAgreement = RentalAgreement::query()

            ->with([

                'agreement.apartment',

                'agreement.tenant',
            ])

            ->whereHas(

                'agreement',

                fn ($query) =>

                $query

                    ->where(
                        'company_id',
                        $request->user()->company_id
                    )

                    ->where(
                        'id',
                        $id
                    )
            )

            ->first();

        if (! $rentalAgreement) {

            return response()->json([

                'success' => false,

                'message' =>

                    'Rental agreement not found.',
            ], 404);
        }

        return response()->json([

            'success' => true,

            'data' =>

                new RentalAgreementResource(

                    $rentalAgreement
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Rental Agreement
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {

        DB::beginTransaction();

        try {

            $rentalAgreement = RentalAgreement::query()

                ->with('agreement')

                ->whereHas(

                    'agreement',

                    fn ($query) =>

                    $query

                        ->where(
                            'company_id',
                            $request->user()->company_id
                        )

                        ->where(
                            'id',
                            $id
                        )
                )

                ->first();

            if (! $rentalAgreement) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' =>

                        'Rental agreement not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Deleting Active Agreements
            |--------------------------------------------------------------------------
            */

            if (

                $rentalAgreement
                    ->agreement
                    ->status

                === Agreement::STATUS_ACTIVE
            ) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' =>

                        'Active agreements cannot be deleted.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Soft Delete
            |--------------------------------------------------------------------------
            */

            $rentalAgreement->delete();

            $rentalAgreement
                ->agreement
                ?->delete();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' =>

                    'Rental agreement deleted successfully.',
            ]);

        } catch (Throwable $exception) {

            DB::rollBack();

            report($exception);

            return response()->json([

                'success' => false,

                'message' =>

                    'Failed to delete rental agreement.',
            ], 500);
        }
    }
    //activate and terminate methods
    /*
|--------------------------------------------------------------------------
| Activate Rental Agreement
|--------------------------------------------------------------------------
*/

public function activate(
    Request $request,
    string $id
): JsonResponse {

    DB::beginTransaction();

    try {

        $rentalAgreement = RentalAgreement::query()

            ->with([

                'agreement',

                'agreement.apartment',

                'agreement.tenant',
            ])

            ->whereHas(

                'agreement',

                fn ($query) =>

                $query

                    ->where(
                        'company_id',
                        $request->user()->company_id
                    )

                    ->where(
                        'id',
                        $id
                    )
            )

            ->lockForUpdate()

            ->first();

        /*
        |--------------------------------------------------------------------------
        | Agreement Not Found
        |--------------------------------------------------------------------------
        */

        if (! $rentalAgreement) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Rental agreement not found.',
            ], 404);
        }

        $agreement = $rentalAgreement->agreement;

        $apartment = $agreement->apartment;

        /*
        |--------------------------------------------------------------------------
        | Business Rule Validation
        |--------------------------------------------------------------------------
        */

        if (! $agreement->canBeActivated()) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Agreement cannot be activated from current status.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Apartment Must Be Available
        |--------------------------------------------------------------------------
        */

        if (

            $apartment->inventory_status
            !== Apartment::STATUS_AVAILABLE
        ) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Apartment is not available for activation.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent Multiple Active Agreements
        |--------------------------------------------------------------------------
        */

        $anotherActiveAgreementExists = Agreement::query()

            ->where(
                'apartment_id',
                $apartment->id
            )

            ->where(
                'status',
                Agreement::STATUS_ACTIVE
            )

            ->where(
                'id',
                '!=',
                $agreement->id
            )

            ->exists();

        if ($anotherActiveAgreementExists) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Apartment already has another active agreement.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Activate Agreement
        |--------------------------------------------------------------------------
        */

        $agreement->update([

            'status' =>

                Agreement::STATUS_ACTIVE,

            'approved_at' =>

                now(),

            'approved_by' =>

                $request->user()->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Occupy Apartment
        |--------------------------------------------------------------------------
        */

        $apartment->update([

            'inventory_status' =>

                Apartment::STATUS_OCCUPIED,
        ]);

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Reload Relationships
        |--------------------------------------------------------------------------
        */

        $rentalAgreement->refresh()->load([

            'agreement.apartment',

            'agreement.tenant',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Rental agreement activated successfully.',

            'data' =>

                new RentalAgreementResource(

                    $rentalAgreement
                ),
        ]);

    } catch (Throwable $exception) {

        DB::rollBack();

        report($exception);

        return response()->json([

            'success' => false,

            'message' =>

                'Failed to activate rental agreement.',

            'error' =>

                app()->environment('local')
                    ? $exception->getMessage()
                    : null,
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| Terminate Rental Agreement
|--------------------------------------------------------------------------
*/

public function terminate(
    Request $request,
    string $id
): JsonResponse {

    $request->validate([

        'termination_reason' => [

            'required',

            'string',

            'max:5000',
        ],
    ]);

    DB::beginTransaction();

    try {

        $rentalAgreement = RentalAgreement::query()

            ->with([

                'agreement',

                'agreement.apartment',

                'agreement.tenant',
            ])

            ->whereHas(

                'agreement',

                fn ($query) =>

                $query

                    ->where(
                        'company_id',
                        $request->user()->company_id
                    )

                    ->where(
                        'id',
                        $id
                    )
            )

            ->lockForUpdate()

            ->first();

        /*
        |--------------------------------------------------------------------------
        | Agreement Not Found
        |--------------------------------------------------------------------------
        */

        if (! $rentalAgreement) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Rental agreement not found.',
            ], 404);
        }

        $agreement = $rentalAgreement->agreement;

        $apartment = $agreement->apartment;

        /*
        |--------------------------------------------------------------------------
        | Only Active Agreements Can Be Terminated
        |--------------------------------------------------------------------------
        */

        if (

            $agreement->status
            !== Agreement::STATUS_ACTIVE
        ) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Only active agreements can be terminated.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Agreement Lifecycle
        |--------------------------------------------------------------------------
        */

        $agreement->update([

            'status' =>

                Agreement::STATUS_TERMINATED,

            'terminated_at' =>

                now(),

            'terminated_by' =>

                $request->user()->id,

            'termination_reason' =>

                $request->termination_reason,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Release Apartment
        |--------------------------------------------------------------------------
        */

        $apartment->update([

            'inventory_status' =>

                Apartment::STATUS_AVAILABLE,
        ]);

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Reload Relationships
        |--------------------------------------------------------------------------
        */

        $rentalAgreement->refresh()->load([

            'agreement.apartment',

            'agreement.tenant',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Rental agreement terminated successfully.',

            'data' =>

                new RentalAgreementResource(

                    $rentalAgreement
                ),
        ]);

    } catch (Throwable $exception) {

        DB::rollBack();

        report($exception);

        return response()->json([

            'success' => false,

            'message' =>

                'Failed to terminate rental agreement.',

            'error' =>

                app()->environment('local')
                    ? $exception->getMessage()
                    : null,
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| Update Rental Agreement
|--------------------------------------------------------------------------
*/

public function update(
    UpdateRentalAgreementRequest $request,
    string $id
): JsonResponse {

    DB::beginTransaction();

    try {

        $rentalAgreement = RentalAgreement::query()

            ->with([

                'agreement',

                'agreement.apartment',

                'agreement.tenant',
            ])

            ->whereHas(

                'agreement',

                fn ($query) =>

                $query

                    ->where(
                        'company_id',
                        $request->user()->company_id
                    )

                    ->where(
                        'id',
                        $id
                    )
            )

            ->lockForUpdate()

            ->first();

        /*
        |--------------------------------------------------------------------------
        | Agreement Not Found
        |--------------------------------------------------------------------------
        */

        if (! $rentalAgreement) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>

                    'Rental agreement not found.',
            ], 404);
        }

        $agreement = $rentalAgreement->agreement;

        /*
        |--------------------------------------------------------------------------
        | Update Base Agreement
        |--------------------------------------------------------------------------
        */

        $agreementPayload = [];

        if ($request->has('start_date')) {

            $agreementPayload['start_date'] =

                $request->start_date;
        }

        if ($request->has('end_date')) {

            $agreementPayload['end_date'] =

                $request->end_date;
        }

        if ($request->has('signed_at')) {

            $agreementPayload['signed_at'] =

                $request->signed_at;
        }

        if ($request->has('contract_amount')) {

            $agreementPayload['contract_amount'] =

                $request->contract_amount;
        }

        if ($request->has('currency')) {

            $agreementPayload['currency'] =

                strtoupper(
                    $request->currency
                );
        }

        if ($request->has('notes')) {

            $agreementPayload['notes'] =

                $request->notes;
        }

        /*
        |--------------------------------------------------------------------------
        | Audit Trail
        |--------------------------------------------------------------------------
        */

        $agreementPayload['updated_by'] =

            $request->user()->id;

        /*
        |--------------------------------------------------------------------------
        | Replace Contract File
        |--------------------------------------------------------------------------
        */

        if (

            $request->hasFile(
                'contract_file'
            )

        ) {

            $contractFilePath =

                $request->file(
                    'contract_file'
                )->store(

                    'contracts/rental',

                    'public'
                );

            $agreementPayload['contract_file_path'] =

                $contractFilePath;
        }

        /*
        |--------------------------------------------------------------------------
        | Persist Agreement Changes
        |--------------------------------------------------------------------------
        */

        if (! empty($agreementPayload)) {

            $agreement->update(
                $agreementPayload
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update Rental Agreement
        |--------------------------------------------------------------------------
        */

        $rentalPayload = [];

        if ($request->has('monthly_rent')) {

            $rentalPayload['monthly_rent'] =

                $request->monthly_rent;
        }

        if ($request->has('security_deposit')) {

    $rentalPayload['security_deposit'] =

        $request->security_deposit;
}

        if ($request->has('payment_due_day')) {

            $rentalPayload['payment_due_day'] =

                $request->payment_due_day;
        }

        if ($request->has('includes_water')) {

            $rentalPayload['includes_water'] =

                $request->boolean(
                    'includes_water'
                );
        }

        if ($request->has('includes_electricity')) {

            $rentalPayload['includes_electricity'] =

                $request->boolean(
                    'includes_electricity'
                );
        }

        if ($request->has('includes_internet')) {

            $rentalPayload['includes_internet'] =

                $request->boolean(
                    'includes_internet'
                );
        }

        if ($request->has('auto_renew')) {

            $rentalPayload['auto_renew'] =

                $request->boolean(
                    'auto_renew'
                );
        }

        if ($request->has('renewal_notice_days')) {

            $rentalPayload['renewal_notice_days'] =

                $request->renewal_notice_days;
        }

        if ($request->has('special_terms')) {

            $rentalPayload['special_terms'] =

                $request->special_terms;
        }

        /*
        |--------------------------------------------------------------------------
        | Persist Rental Agreement Changes
        |--------------------------------------------------------------------------
        */

        if (! empty($rentalPayload)) {

            $rentalAgreement->update(
                $rentalPayload
            );
        }

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Reload Relationships
        |--------------------------------------------------------------------------
        */

        $rentalAgreement->refresh()->load([

            'agreement.apartment',

            'agreement.tenant',
        ]);

        return response()->json([

            'success' => true,

            'message' =>

                'Rental agreement updated successfully.',

            'data' =>

                new RentalAgreementResource(

                    $rentalAgreement
                ),
        ]);

    } catch (Throwable $exception) {

        DB::rollBack();

        report($exception);

        return response()->json([

            'success' => false,

            'message' =>

                'Failed to update rental agreement.',

            'error' =>

                app()->environment('local')
                    ? $exception->getMessage()
                    : null,
        ], 500);
    }
}
}