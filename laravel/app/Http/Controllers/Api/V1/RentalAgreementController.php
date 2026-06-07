<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRentalAgreementRequest;
use App\Http\Requests\Api\V1\UpdateRentalAgreementRequest;
use App\Http\Resources\Api\V1\MonthlyInvoiceResource;
use App\Http\Resources\Api\V1\RentalAgreementResource;
use App\Models\RentalAgreement;
use App\Services\Billing\ConsolidationResult;
use App\Services\Billing\InvoiceConsolidationService;
use App\Services\Property\RentalAgreementService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RentalAgreementController extends Controller
{
    public function __construct(
        private readonly RentalAgreementService $rentalAgreements,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agreements = RentalAgreement::query()
            ->with([
                'agreement.apartment.building',
                'agreement.tenant',
            ])
            ->whereHas(
                'agreement',
                fn ($query) => $query->where('company_id', $request->user()->company_id)
            )
            ->latest()
            ->paginate($request->integer('per_page', 100));

        return response()->json([
            'success' => true,
            'message' => 'Rental agreements retrieved successfully.',
            'data' => RentalAgreementResource::collection($agreements),
            'meta' => [
                'current_page' => $agreements->currentPage(),
                'last_page' => $agreements->lastPage(),
                'per_page' => $agreements->perPage(),
                'total' => $agreements->total(),
            ],
        ]);
    }

    public function store(StoreRentalAgreementRequest $request): JsonResponse
    {
        try {
            $rental = $this->rentalAgreements->create(
                $request->user(),
                $request->validated(),
                $request->file('contract_file'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Rental agreement created successfully.',
                'data' => new RentalAgreementResource($rental),
            ], 201);
        } catch (UniqueConstraintViolationException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Could not assign a unique agreement number. Please try again.',
                'errors' => [
                    'agreement_number' => [
                        'An agreement with this number already exists. Retry the save.',
                    ],
                ],
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create rental agreement.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $rentalAgreement = RentalAgreement::query()
            ->with([
                'agreement.apartment.building',
                'agreement.tenant',
                'agreement.agreementCharges.chargeModel',
                'agreement.agreementCharges.chargeType',
            ])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $request->user()->company_id)
                    ->where('id', $id)
            )
            ->first();

        if (! $rentalAgreement) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RentalAgreementResource($rentalAgreement),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->rentalAgreements->delete($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Rental agreement deleted successfully.',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rental agreement.',
            ], 500);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $rental = $this->rentalAgreements->approve($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Rental agreement approved successfully.',
                'data' => new RentalAgreementResource($rental),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve rental agreement.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $rental = $this->rentalAgreements->activate($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Rental agreement activated successfully.',
                'data' => new RentalAgreementResource($rental),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate rental agreement.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function terminate(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'termination_reason' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $rental = $this->rentalAgreements->terminate(
                $request->user(),
                $id,
                $request->termination_reason,
            );

            return response()->json([
                'success' => true,
                'message' => 'Rental agreement terminated successfully.',
                'data' => new RentalAgreementResource($rental),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate rental agreement.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function consolidateBilling(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|between:2020,2050',
            'month' => 'required|integer|between:1,12',
        ]);

        $rental = RentalAgreement::query()
            ->with(['agreement.apartment'])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $request->user()->company_id)
                    ->where('id', $id)
            )
            ->first();

        if (! $rental) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        }

        $billingDate = Carbon::create(
            (int) $validated['year'],
            (int) $validated['month'],
            1
        )->startOfMonth();

        $result = app(InvoiceConsolidationService::class, ['currentUser' => $request->user()])
            ->consolidate($rental, $billingDate);

        if ($result->wasCreated()) {
            return response()->json([
                'success' => true,
                'message' => 'Draft invoice created for this billing period.',
                'outcome' => $result->outcome,
                'data' => new MonthlyInvoiceResource($result->invoice?->load('lineItems')),
            ], 201);
        }

        if ($result->wasAppended()) {
            return response()->json([
                'success' => true,
                'message' => 'Pending charges were added to the existing invoice for this period.',
                'outcome' => $result->outcome,
                'data' => new MonthlyInvoiceResource($result->invoice?->load('lineItems')),
            ]);
        }

        $messages = [
            ConsolidationResult::OUTCOME_SKIPPED_NO_ITEMS =>
                'No approved utility charges or billing items are ready for this period. Approve charges under Charges, then try again.',
            ConsolidationResult::OUTCOME_SKIPPED_ALREADY_EXISTS =>
                'An invoice exists for this period but there are no new charges to add.',
        ];

        return response()->json([
            'success' => false,
            'message' => $messages[$result->outcome] ?? 'Billing consolidation did not apply any changes.',
            'outcome' => $result->outcome,
            'data' => $result->invoice
                ? new MonthlyInvoiceResource($result->invoice)
                : null,
        ], 422);
    }

    public function update(UpdateRentalAgreementRequest $request, string $id): JsonResponse
    {
        try {
            $rental = $this->rentalAgreements->update(
                $request->user(),
                $id,
                $request->validated(),
                $request->file('contract_file'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Rental agreement updated successfully.',
                'data' => new RentalAgreementResource($rental),
            ]);
        } catch (BusinessRuleException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Rental agreement not found.',
            ], 404);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update rental agreement.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }
}
