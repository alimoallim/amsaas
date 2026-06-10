<?php

namespace App\Services\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\ApartmentOwnershipHistory;
use App\Models\SaleAgreement;
use App\Models\SaleOwnershipApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OwnershipTransferService
{
    public function __construct(
        private readonly SaleLegalDocumentService $documents,
    ) {}

    /**
     * Called when a sale is financially completed — awaits approval chain before legal transfer.
     */
    public function onSaleCompleted(User $actor, SaleAgreement $sale): void
    {
        $sale->update([
            'ownership_transferred' => false,
            'ownership_transfer_date' => null,
            'closing_date' => now()->toDateString(),
        ]);

        $path = $this->documents->generateCompletionCertificate(
            $sale->fresh(['agreement.apartment.building', 'agreement.buyer']),
        );

        if ($path) {
            $sale->update(['completion_certificate_path' => $path]);
        }
    }

    /**
     * @return array{sale: SaleAgreement, finalized: bool, message: string}
     */
    public function approve(User $actor, string $agreementId, string $step, ?string $notes = null): array
    {
        return DB::transaction(function () use ($actor, $agreementId, $step, $notes) {
            $sale = $this->resolveCompletedSale($actor, $agreementId);

            if (! in_array($step, SaleOwnershipApproval::STEPS, true)) {
                throw new BusinessRuleException(
                    'Invalid ownership approval step.',
                    'OWNERSHIP_INVALID_STEP',
                );
            }

            if ($sale->ownership_transferred) {
                throw new BusinessRuleException(
                    'Ownership has already been transferred for this contract.',
                    'OWNERSHIP_ALREADY_TRANSFERRED',
                );
            }

            $exists = SaleOwnershipApproval::query()
                ->where('sale_agreement_id', $sale->id)
                ->where('step', $step)
                ->exists();

            if ($exists) {
                throw new BusinessRuleException(
                    sprintf('The %s approval has already been recorded.', SaleOwnershipApproval::stepLabel($step)),
                    'OWNERSHIP_STEP_ALREADY_APPROVED',
                );
            }

            SaleOwnershipApproval::create([
                'company_id' => $actor->company_id,
                'sale_agreement_id' => $sale->id,
                'step' => $step,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            $finalized = $this->tryFinalize($actor, $sale->fresh(['agreement.buyer', 'agreement.apartment']));

            return [
                'sale' => $this->reloadSale($sale->id),
                'finalized' => $finalized,
                'message' => $finalized
                    ? 'All approvals recorded. Ownership transferred.'
                    : sprintf('%s approval recorded.', SaleOwnershipApproval::stepLabel($step)),
            ];
        });
    }

    public function issueTitleDeed(User $actor, string $agreementId, string $titleDeedNumber, ?string $notes = null): SaleAgreement
    {
        return DB::transaction(function () use ($actor, $agreementId, $titleDeedNumber, $notes) {
            $sale = $this->resolveCompletedSale($actor, $agreementId);

            if (! $sale->ownership_transferred) {
                throw new BusinessRuleException(
                    'Ownership must be fully approved before issuing a title deed.',
                    'OWNERSHIP_NOT_TRANSFERRED',
                );
            }

            if ($sale->title_deed_issued) {
                throw new BusinessRuleException(
                    'Title deed has already been issued for this contract.',
                    'TITLE_DEED_ALREADY_ISSUED',
                );
            }

            $sale->update([
                'title_deed_issued' => true,
                'title_deed_number' => $titleDeedNumber,
            ]);

            ApartmentOwnershipHistory::query()
                ->where('sale_agreement_id', $sale->id)
                ->update([
                    'title_deed_number' => $titleDeedNumber,
                    'notes' => $notes,
                ]);

            return $this->reloadSale($sale->id);
        });
    }

    /** @return list<string> */
    public function pendingSteps(SaleAgreement $sale): array
    {
        $approved = SaleOwnershipApproval::query()
            ->where('sale_agreement_id', $sale->id)
            ->pluck('step')
            ->all();

        return array_values(array_diff(SaleOwnershipApproval::STEPS, $approved));
    }

    public function isFullyApproved(SaleAgreement $sale): bool
    {
        return count($this->pendingSteps($sale)) === 0;
    }

    private function tryFinalize(User $actor, SaleAgreement $sale): bool
    {
        if (! $this->isFullyApproved($sale)) {
            return false;
        }

        if ($sale->ownership_transferred) {
            return true;
        }

        $agreement = $sale->agreement;
        $transferDate = now()->toDateString();

        $sale->update([
            'ownership_transferred' => true,
            'ownership_transfer_date' => $transferDate,
        ]);

        ApartmentOwnershipHistory::create([
            'company_id' => $actor->company_id,
            'apartment_id' => $agreement->apartment_id,
            'buyer_id' => $agreement->buyer_id,
            'sale_agreement_id' => $sale->id,
            'transfer_date' => $transferDate,
            'recorded_by' => $actor->id,
        ]);

        $path = $this->documents->generateOwnershipTransferCertificate($sale->fresh(['agreement.buyer', 'agreement.apartment.building']));
        if ($path) {
            $sale->update(['ownership_transfer_certificate_path' => $path]);
        }

        return true;
    }

    private function resolveCompletedSale(User $actor, string $agreementId): SaleAgreement
    {
        $sale = SaleAgreement::query()
            ->with(['agreement', 'ownershipApprovals'])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $actor->company_id)
                    ->where('id', $agreementId),
            )
            ->lockForUpdate()
            ->firstOrFail();

        if ($sale->agreement->status !== Agreement::STATUS_COMPLETED) {
            throw new BusinessRuleException(
                'Ownership transfer applies only to completed sale contracts.',
                'SALE_NOT_COMPLETED',
            );
        }

        return $sale;
    }

    private function reloadSale(string $id): SaleAgreement
    {
        return SaleAgreement::query()
            ->with([
                'agreement.apartment.building',
                'agreement.buyer',
                'ownershipApprovals.approvedBy',
                'installmentSchedules',
            ])
            ->findOrFail($id);
    }
}
