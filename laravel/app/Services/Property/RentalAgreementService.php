<?php

namespace App\Services\Property;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementNumberService;
use App\Services\Agreements\AgreementStateMachine;
use App\Services\Billing\AgreementBillingResyncService;
use App\Services\Billing\AgreementChargeSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RentalAgreementService
{
    public function __construct(
        private readonly AgreementStateMachine $stateMachine,
        private readonly ApartmentInventoryService $inventory,
        private readonly TenantLeaseEligibilityService $tenantEligibility,
        private readonly AgreementChargeSyncService $agreementCharges,
    ) {}

    public function create(
        User $actor,
        array $data,
        ?UploadedFile $contractFile = null,
    ): RentalAgreement {
        return DB::transaction(function () use ($actor, $data, $contractFile) {
            $apartment = Apartment::query()
                ->where('id', $data['apartment_id'])
                ->where('company_id', $actor->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $tenant = Tenant::query()
                ->where('id', $data['tenant_id'])
                ->where('company_id', $actor->company_id)
                ->firstOrFail();

            $this->tenantEligibility->assertCanSignLease($tenant);
            $this->inventory->assertRentable($apartment);
            $this->inventory->assertNoConflictingLease($apartment);

            $status = self::resolveAgreementStatus($data['status'] ?? null);

            $agreement = Agreement::create([
                'company_id' => $actor->company_id,
                'agreement_number' => AgreementNumberService::allocate(
                    Agreement::TYPE_RENTAL,
                ),
                'agreement_type' => Agreement::TYPE_RENTAL,
                'apartment_id' => $apartment->id,
                'tenant_id' => $tenant->id,
                'status' => $status,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'signed_at' => $data['signed_at'] ?? null,
                'contract_amount' => $data['contract_amount'] ?? $data['monthly_rent'],
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            if ($contractFile) {
                $agreement->update([
                    'contract_file_path' => $contractFile->store('contracts/rental', 'public'),
                ]);
            }

            $rental = RentalAgreement::create([
                'id' => $agreement->id,
                'monthly_rent' => $data['monthly_rent'],
                'security_deposit' => $data['security_deposit'] ?? 0,
                'payment_due_day' => $data['payment_due_day'],
                'includes_water' => (bool) ($data['includes_water'] ?? false),
                'includes_electricity' => (bool) ($data['includes_electricity'] ?? false),
                'includes_internet' => (bool) ($data['includes_internet'] ?? false),
                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'renewal_notice_days' => $data['renewal_notice_days'] ?? 30,
                'special_terms' => $data['special_terms'] ?? null,
            ]);

            if ($status === Agreement::STATUS_ACTIVE) {
                $this->activateAgreement($agreement, $apartment, $actor);
            } elseif ($status === Agreement::STATUS_DRAFT) {
                $this->inventory->markReserved($apartment);
            }

            $this->agreementCharges->sync(
                $actor,
                $agreement,
                $rental,
                $data['recurring_charges'] ?? [],
                $data['rent_charge_model_id'] ?? null,
            );

            $rental = $rental->fresh()->load([
                'agreement.apartment.building',
                'agreement.tenant',
                'agreement.agreementCharges.chargeModel',
                'agreement.agreementCharges.chargeType',
            ]);

            $this->resyncInvoicesIfActive($actor, $rental);

            return $rental;
        });
    }

    public function approve(User $actor, string $agreementId): RentalAgreement
    {
        return DB::transaction(function () use ($actor, $agreementId) {
            $rental = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $rental->agreement;

            if (! $this->stateMachine->canTransition($agreement, Agreement::STATUS_APPROVED)) {
                throw new BusinessRuleException(
                    'Agreement cannot be approved from its current status.',
                    'AGREEMENT_CANNOT_APPROVE',
                );
            }

            $this->stateMachine->transition($agreement, Agreement::STATUS_APPROVED, $actor);

            return $rental->fresh()->load(['agreement.apartment.building', 'agreement.tenant']);
        });
    }

    public function activate(User $actor, string $agreementId): RentalAgreement
    {
        return DB::transaction(function () use ($actor, $agreementId) {
            $rental = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $rental->agreement;
            $apartment = $agreement->apartment;

            if (! $agreement->canBeActivated()) {
                throw new BusinessRuleException(
                    'Agreement cannot be activated from its current status.',
                    'AGREEMENT_CANNOT_ACTIVATE',
                );
            }

            $this->inventory->assertCanActivate($apartment);
            $this->inventory->assertNoConflictingLease($apartment, $agreement->id);

            $this->activateAgreement($agreement, $apartment, $actor);

            return $rental->fresh()->load(['agreement.apartment.building', 'agreement.tenant']);
        });
    }

    public function terminate(
        User $actor,
        string $agreementId,
        string $terminationReason,
    ): RentalAgreement {
        return DB::transaction(function () use ($actor, $agreementId, $terminationReason) {
            $rental = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $rental->agreement;
            $apartment = $agreement->apartment;

            if ($agreement->status !== Agreement::STATUS_ACTIVE) {
                throw new BusinessRuleException(
                    'Only active agreements can be terminated.',
                    'AGREEMENT_NOT_ACTIVE',
                );
            }

            $this->stateMachine->transition(
                $agreement,
                Agreement::STATUS_TERMINATED,
                $actor,
                ['termination_reason' => $terminationReason],
            );

            $this->inventory->release($apartment);

            return $rental->fresh()->load(['agreement.apartment.building', 'agreement.tenant']);
        });
    }

    public function update(
        User $actor,
        string $agreementId,
        array $data,
        ?UploadedFile $contractFile = null,
    ): RentalAgreement {
        return DB::transaction(function () use ($actor, $agreementId, $data, $contractFile) {
            $rental = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $rental->agreement;

            $this->assertAgreementEditable($agreement);

            if (isset($data['status']) && $data['status'] !== $agreement->status) {
                $this->applyStatusChange($agreement, $data['status'], $actor);
            }

            $this->applyCoreFieldUpdates($actor, $agreement, $data);
            $agreement->refresh();

            $agreementPayload = array_filter([
                'end_date' => array_key_exists('end_date', $data) ? $data['end_date'] : null,
                'signed_at' => $data['signed_at'] ?? null,
                'contract_amount' => $data['contract_amount'] ?? null,
                'currency' => isset($data['currency']) ? strtoupper($data['currency']) : null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor->id,
            ], fn ($v) => $v !== null);

            if ($contractFile) {
                $agreementPayload['contract_file_path'] = $contractFile->store('contracts/rental', 'public');
            }

            if ($agreementPayload !== []) {
                $agreement->update($agreementPayload);
            }

            $rentalPayload = array_filter([
                'monthly_rent' => $data['monthly_rent'] ?? null,
                'security_deposit' => $data['security_deposit'] ?? null,
                'payment_due_day' => $data['payment_due_day'] ?? null,
                'includes_water' => array_key_exists('includes_water', $data) ? (bool) $data['includes_water'] : null,
                'includes_electricity' => array_key_exists('includes_electricity', $data) ? (bool) $data['includes_electricity'] : null,
                'includes_internet' => array_key_exists('includes_internet', $data) ? (bool) $data['includes_internet'] : null,
                'auto_renew' => array_key_exists('auto_renew', $data) ? (bool) $data['auto_renew'] : null,
                'renewal_notice_days' => $data['renewal_notice_days'] ?? null,
                'special_terms' => $data['special_terms'] ?? null,
            ], fn ($v) => $v !== null);

            if ($rentalPayload !== []) {
                $rental->update($rentalPayload);
            }

            if (
                array_key_exists('recurring_charges', $data)
                || array_key_exists('rent_charge_model_id', $data)
            ) {
                $this->agreementCharges->sync(
                    $actor,
                    $agreement->fresh(),
                    $rental->fresh(),
                    $data['recurring_charges'] ?? [],
                    $data['rent_charge_model_id'] ?? null,
                );
            }

            $rental = $rental->fresh()->load([
                'agreement.apartment.building',
                'agreement.tenant',
                'agreement.agreementCharges.chargeModel',
                'agreement.agreementCharges.chargeType',
            ]);

            $this->resyncInvoicesIfActive($actor, $rental);

            return $rental;
        });
    }

    public function delete(User $actor, string $agreementId): void
    {
        DB::transaction(function () use ($actor, $agreementId) {
            $rental = $this->resolveForCompany($actor->company_id, $agreementId);
            $agreement = $rental->agreement;
            $apartment = $agreement->apartment;

            if ($agreement->status === Agreement::STATUS_ACTIVE) {
                throw new BusinessRuleException(
                    'Active agreements cannot be deleted. Terminate the agreement first.',
                    'AGREEMENT_ACTIVE',
                );
            }

            $wasDraft = $agreement->status === Agreement::STATUS_DRAFT;

            $rental->delete();
            $agreement->delete();

            if ($wasDraft && $apartment && ! $this->inventory->hasConflictingLease($apartment)) {
                $this->inventory->release($apartment);
            }
        });
    }

    private static function resolveAgreementStatus(?string $status): string
    {
        $map = [
            'pending' => Agreement::STATUS_PENDING_APPROVAL,
        ];

        if ($status && isset($map[$status])) {
            return $map[$status];
        }

        $allowed = [
            Agreement::STATUS_DRAFT,
            Agreement::STATUS_ACTIVE,
            Agreement::STATUS_PENDING_APPROVAL,
        ];

        if ($status && in_array($status, $allowed, true)) {
            return $status;
        }

        return Agreement::STATUS_DRAFT;
    }

    private function activateAgreement(
        Agreement $agreement,
        Apartment $apartment,
        User $actor,
    ): void {
        if ($agreement->status !== Agreement::STATUS_ACTIVE) {
            if (! $this->stateMachine->canTransition($agreement, Agreement::STATUS_ACTIVE)) {
                throw new BusinessRuleException(
                    'Agreement cannot be activated from its current status.',
                    'AGREEMENT_CANNOT_ACTIVATE',
                );
            }

            $this->stateMachine->transition($agreement, Agreement::STATUS_ACTIVE, $actor);
        }

        $this->inventory->occupy($apartment);

        AgreementCharge::query()
            ->where('agreement_id', $agreement->id)
            ->where('status', AgreementCharge::STATUS_DRAFT)
            ->update(['status' => AgreementCharge::STATUS_ACTIVE]);
    }

    /**
     * @throws BusinessRuleException
     */
    private function applyStatusChange(
        Agreement $agreement,
        string $newStatus,
        User $actor,
    ): void {
        if ($newStatus === $agreement->status) {
            return;
        }

        if ($newStatus === Agreement::STATUS_ACTIVE) {
            $this->inventory->assertCanActivate($agreement->apartment);
            $this->inventory->assertNoConflictingLease($agreement->apartment, $agreement->id);
            $this->activateAgreement($agreement, $agreement->apartment, $actor);

            return;
        }

        if ($newStatus === Agreement::STATUS_TERMINATED) {
            throw new BusinessRuleException(
                'Use the terminate endpoint to end an active agreement.',
                'USE_TERMINATE_ENDPOINT',
            );
        }

        if (! $this->stateMachine->canTransition($agreement, $newStatus)) {
            throw new BusinessRuleException(
                "Invalid agreement transition from [{$agreement->status}] to [{$newStatus}].",
                'AGREEMENT_INVALID_TRANSITION',
            );
        }

        $this->stateMachine->transition($agreement, $newStatus, $actor);

        if (
            $newStatus === Agreement::STATUS_CANCELLED
            && $agreement->apartment
            && ! $this->inventory->hasConflictingLease($agreement->apartment, $agreement->id)
        ) {
            $this->inventory->release($agreement->apartment);
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function assertAgreementEditable(Agreement $agreement): void
    {
        if (in_array($agreement->status, [
            Agreement::STATUS_TERMINATED,
            Agreement::STATUS_COMPLETED,
            Agreement::STATUS_CANCELLED,
        ], true)) {
            throw new BusinessRuleException(
                'This agreement can no longer be modified.',
                'AGREEMENT_FINALIZED',
            );
        }
    }

    /**
     * Apply unit, tenant, and start-date corrections (including on active agreements).
     *
     * @param  array<string, mixed>  $data
     */
    private function applyCoreFieldUpdates(User $actor, Agreement $agreement, array $data): void
    {
        if (array_key_exists('tenant_id', $data) && $data['tenant_id'] !== $agreement->tenant_id) {
            $tenant = Tenant::query()
                ->where('id', $data['tenant_id'])
                ->where('company_id', $actor->company_id)
                ->firstOrFail();

            $this->tenantEligibility->assertCanSignLease($tenant);

            $agreement->update([
                'tenant_id' => $tenant->id,
                'updated_by' => $actor->id,
            ]);
        }

        if (array_key_exists('apartment_id', $data) && $data['apartment_id'] !== $agreement->apartment_id) {
            $newApartment = Apartment::query()
                ->where('id', $data['apartment_id'])
                ->where('company_id', $actor->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldApartment = $agreement->apartment;

            $this->inventory->assertRentable($newApartment);
            $this->inventory->assertNoConflictingLease($newApartment, $agreement->id);

            if ($agreement->status === Agreement::STATUS_ACTIVE) {
                $this->inventory->assertCanActivate($newApartment);

                if ($oldApartment) {
                    $this->inventory->release($oldApartment);
                }

                $this->inventory->occupy($newApartment);
            } elseif ($agreement->status !== Agreement::STATUS_ACTIVE) {
                if (
                    $oldApartment
                    && ! $this->inventory->hasConflictingLease($oldApartment, $agreement->id)
                ) {
                    $this->inventory->release($oldApartment);
                }

                if ($agreement->status === Agreement::STATUS_DRAFT) {
                    $this->inventory->markReserved($newApartment);
                }
            }

            $agreement->update([
                'apartment_id' => $newApartment->id,
                'updated_by' => $actor->id,
            ]);

            MonthlyInvoice::query()
                ->where('company_id', $actor->company_id)
                ->where('contract_type', 'rental')
                ->where('contract_id', $agreement->id)
                ->where('status', 'draft')
                ->update(['apartment_id' => $newApartment->id]);
        }

        if (array_key_exists('start_date', $data)) {
            $newStart = $data['start_date'];
            $currentStart = $agreement->start_date?->toDateString();

            if ($newStart !== $currentStart) {
                $agreement->update([
                    'start_date' => $newStart,
                    'updated_by' => $actor->id,
                ]);

                AgreementCharge::query()
                    ->where('agreement_id', $agreement->id)
                    ->update(['billing_start_date' => $newStart]);
            }
        }
    }

    private function resyncInvoicesIfActive(User $actor, RentalAgreement $rental): void
    {
        if ($rental->agreement?->status !== Agreement::STATUS_ACTIVE) {
            return;
        }

        app(AgreementBillingResyncService::class)->resyncAfterAgreementChange($actor, $rental);
    }

    private function resolveForCompany(
        string $companyId,
        string $agreementId,
        bool $lock = false,
    ): RentalAgreement {
        $query = RentalAgreement::query()
            ->with(['agreement.apartment', 'agreement.tenant'])
            ->whereHas('agreement', fn ($q) => $q
                ->where('company_id', $companyId)
                ->where('id', $agreementId));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
