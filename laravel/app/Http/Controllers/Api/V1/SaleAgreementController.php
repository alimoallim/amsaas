<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApplySaleDepositRequest;
use App\Http\Requests\Api\V1\ApproveOwnershipTransferRequest;
use App\Http\Requests\Api\V1\IssueTitleDeedRequest;
use App\Http\Requests\Api\V1\RecordSaleInstallmentPaymentRequest;
use App\Http\Requests\Api\V1\RecordSalePaymentRequest;
use App\Http\Requests\Api\V1\StoreSaleAgreementRequest;
use App\Http\Requests\Api\V1\UpdateSaleAgreementRequest;
use App\Http\Resources\Api\V1\SaleAgreementResource;
use App\Models\Agreement;
use App\Models\SaleAgreement;
use App\Services\Sales\OwnershipTransferService;
use App\Services\Sales\SaleAgreementPostingService;
use App\Services\Sales\SaleAgreementService;
use App\Services\Sales\SaleLegalDocumentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaleAgreementController extends Controller
{
    public function __construct(
        private readonly SaleAgreementService $saleAgreements,
        private readonly SaleAgreementPostingService $posting,
        private readonly OwnershipTransferService $ownershipTransfers,
        private readonly SaleLegalDocumentService $legalDocuments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agreements = SaleAgreement::query()
            ->with(['agreement.apartment.building', 'agreement.buyer'])
            ->whereHas(
                'agreement',
                fn ($query) => $query->where('company_id', $request->user()->company_id),
            )
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->whereHas(
                    'agreement',
                    fn ($q) => $q->where('status', $request->status),
                );
            })
            ->when($request->filled('buyer_id'), function ($query) use ($request) {
                $query->whereHas(
                    'agreement',
                    fn ($q) => $q->where('buyer_id', $request->buyer_id),
                );
            })
            ->when($request->filled('apartment_id'), function ($query) use ($request) {
                $query->whereHas(
                    'agreement',
                    fn ($q) => $q->where('apartment_id', $request->apartment_id),
                );
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($sub) use ($search) {
                    $sub->whereHas(
                        'agreement',
                        fn ($a) => $a->where('agreement_number', 'ilike', "%{$search}%"),
                    )
                        ->orWhereHas(
                            'agreement.buyer',
                            fn ($b) => $b->where('full_name', 'ilike', "%{$search}%"),
                        )
                        ->orWhereHas(
                            'agreement.apartment',
                            fn ($u) => $u->where('unit_number', 'ilike', "%{$search}%"),
                        );
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Sale agreements retrieved successfully.',
            'data' => SaleAgreementResource::collection($agreements),
            'meta' => [
                'current_page' => $agreements->currentPage(),
                'last_page' => $agreements->lastPage(),
                'per_page' => $agreements->perPage(),
                'total' => $agreements->total(),
            ],
        ]);
    }

    public function store(StoreSaleAgreementRequest $request): JsonResponse
    {
        try {
            $sale = $this->saleAgreements->create(
                $request->user(),
                $request->validated(),
            );

            $executed = $sale->agreement?->status === Agreement::STATUS_ACTIVE;

            return response()->json([
                'success' => true,
                'message' => $executed
                    ? 'Sale contract created and executed.'
                    : 'Sale contract draft created.',
                'data' => new SaleAgreementResource($sale),
            ], 201);
        } catch (UniqueConstraintViolationException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Could not assign a unique agreement number. Please try again.',
            ], 422);
        } catch (Throwable $exception) {
            if ($exception instanceof ModelNotFoundException
                || $exception instanceof BusinessRuleException
                || $exception instanceof ValidationException) {
                throw $exception;
            }

            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sale agreement.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $sale = SaleAgreement::query()
            ->with([
                'agreement.apartment.building',
                'agreement.buyer',
                'paymentAllocations.payment',
                'depositApplications',
                'ownershipApprovals.approvedBy',
            ])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $request->user()->company_id)
                    ->where('id', $id),
            )
            ->first();

        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SaleAgreementResource($sale),
        ]);
    }

    public function update(UpdateSaleAgreementRequest $request, string $id): JsonResponse
    {
        try {
            $sale = $this->saleAgreements->update(
                $request->user(),
                $id,
                $request->validated(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Sale agreement updated.',
                'data' => new SaleAgreementResource($sale),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->saleAgreements->destroy($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Sale agreement deleted.',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function execute(Request $request, string $id): JsonResponse
    {
        try {
            $sale = $this->saleAgreements->execute($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Sale contract executed. Unit is now under contract.',
                'data' => new SaleAgreementResource($sale),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $sale = $this->saleAgreements->cancel(
                $request->user(),
                $id,
                $validated['reason'] ?? null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Sale contract cancelled.',
                'data' => new SaleAgreementResource($sale),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function recordInstallmentPayment(RecordSaleInstallmentPaymentRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->posting->recordInstallmentPayment(
                $request->user(),
                $id,
                $request->validated(),
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new SaleAgreementResource($result['sale']),
                'payment' => [
                    'id' => $result['payment']->id,
                    'receipt_number' => $result['payment']->receipt_number,
                    'amount' => $result['payment']->amount,
                ],
                'completed' => $result['completed'],
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement or instalment not found.',
            ], 404);
        }
    }

    public function applyDeposit(ApplySaleDepositRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->posting->applyReservationDeposit(
                $request->user(),
                $id,
                $request->validated(),
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new SaleAgreementResource($result['sale']),
                'application' => [
                    'id' => $result['application']->id,
                    'amount' => (float) $result['application']->amount,
                    'reservation_number' => $result['application']->saleReservation?->reservation_number,
                ],
                'completed' => $result['completed'],
            ], 201);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function recordPayment(RecordSalePaymentRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->posting->recordPayment(
                $request->user(),
                $id,
                $request->validated(),
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new SaleAgreementResource($result['sale']),
                'payment' => [
                    'id' => $result['payment']->id,
                    'receipt_number' => $result['payment']->receipt_number,
                    'amount' => $result['payment']->amount,
                ],
                'completed' => $result['completed'],
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function downloadCompletionCertificate(Request $request, string $id): BinaryFileResponse|JsonResponse
    {
        $sale = SaleAgreement::query()
            ->with('agreement')
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $request->user()->company_id)
                    ->where('id', $id),
            )
            ->first();

        if (! $sale || ! $sale->completion_certificate_path) {
            return response()->json([
                'success' => false,
                'message' => 'Completion certificate not available.',
            ], 404);
        }

        $diskPath = Storage::disk('local')->path($sale->completion_certificate_path);

        if (! is_file($diskPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate file is missing on storage.',
            ], 404);
        }

        $filename = sprintf(
            '%s-completion.pdf',
            $sale->agreement->agreement_number ?? $sale->id,
        );

        return response()->download($diskPath, $filename);
    }

    public function approveOwnership(ApproveOwnershipTransferRequest $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->ownershipTransfers->approve(
                $request->user(),
                $id,
                $validated['step'],
                $validated['notes'] ?? null,
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new SaleAgreementResource($result['sale']),
                'finalized' => $result['finalized'],
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function issueTitleDeed(IssueTitleDeedRequest $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $sale = $this->ownershipTransfers->issueTitleDeed(
                $request->user(),
                $id,
                $validated['title_deed_number'],
                $validated['notes'] ?? null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Title deed recorded.',
                'data' => new SaleAgreementResource($sale),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }
    }

    public function downloadOwnershipTransferCertificate(Request $request, string $id): BinaryFileResponse|JsonResponse
    {
        $sale = $this->resolveSaleForDocuments($request, $id);

        if (! $sale || ! $sale->ownership_transfer_certificate_path) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership transfer certificate not available.',
            ], 404);
        }

        return $this->downloadStoredPdf(
            $sale->ownership_transfer_certificate_path,
            sprintf('%s-ownership-transfer.pdf', $sale->agreement->agreement_number ?? $sale->id),
            'Ownership transfer certificate file is missing on storage.',
        );
    }

    public function downloadSalesContract(Request $request, string $id): BinaryFileResponse|JsonResponse|\Illuminate\Http\Response
    {
        $sale = $this->resolveSaleForDocuments($request, $id);

        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }

        $filename = sprintf(
            '%s-contract.pdf',
            $sale->agreement->agreement_number ?? $sale->id,
        );

        $binary = $this->legalDocuments->salesContractBinary($sale);

        if ($binary === null || $binary === '') {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate sales contract PDF. Ensure DomPDF is installed.',
            ], 422);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    public function downloadInstallmentSchedule(Request $request, string $id): BinaryFileResponse|JsonResponse|\Illuminate\Http\Response
    {
        return $this->downloadPaymentPlanStatement($request, $id);
    }

    public function downloadPaymentPlanStatement(Request $request, string $id): BinaryFileResponse|JsonResponse|\Illuminate\Http\Response
    {
        $sale = $this->resolveSaleForDocuments($request, $id);

        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }

        if (! $sale->isPaymentPlan()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment plan statement is only available for payment plan contracts.',
            ], 404);
        }

        $filename = sprintf(
            '%s-payment-plan.pdf',
            $sale->agreement->agreement_number ?? $sale->id,
        );

        $binary = $this->legalDocuments->paymentPlanStatementBinary($sale);

        if ($binary === null || $binary === '') {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate payment plan statement PDF. Ensure DomPDF is installed.',
            ], 422);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    public function generateSchedule(Request $request, string $id): JsonResponse
    {
        $sale = SaleAgreement::query()
            ->with(['agreement.apartment.building', 'agreement.buyer'])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $request->user()->company_id)
                    ->where('id', $id),
            )
            ->first();

        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale agreement not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fixed instalment schedules are retired. Payment plans use flexible collection against running balance.',
            'data' => new SaleAgreementResource($sale),
        ]);
    }

    private function resolveSaleForDocuments(Request $request, string $id): ?SaleAgreement
    {
        return SaleAgreement::query()
            ->with([
                'agreement.apartment.building',
                'agreement.buyer',
                'paymentAllocations.payment',
            ])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $request->user()->company_id)
                    ->where('id', $id),
            )
            ->first();
    }

    private function downloadStoredPdf(string $storagePath, string $filename, string $missingMessage): BinaryFileResponse|JsonResponse
    {
        $diskPath = Storage::disk('local')->path($storagePath);

        if (! is_file($diskPath)) {
            return response()->json([
                'success' => false,
                'message' => $missingMessage,
            ], 404);
        }

        return response()->download($diskPath, $filename);
    }
}
