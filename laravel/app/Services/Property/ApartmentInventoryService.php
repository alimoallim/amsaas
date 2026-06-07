<?php

namespace App\Services\Property;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Apartment;

class ApartmentInventoryService
{
    /**
     * Statuses that block another lease on the same unit.
     *
     * @var list<string>
     */
    public const LEASE_BLOCKING_STATUSES = [
        Agreement::STATUS_DRAFT,
        Agreement::STATUS_PENDING_APPROVAL,
        Agreement::STATUS_APPROVED,
        Agreement::STATUS_ACTIVE,
        Agreement::STATUS_RENEWAL_PENDING,
    ];

    /**
     * @throws BusinessRuleException
     */
    public function assertRentable(Apartment $apartment): void
    {
        if (! $apartment->canBeRented()) {
            throw new BusinessRuleException(
                'Apartment is not available for rental (check listing type and inventory status).',
                'APARTMENT_NOT_RENTABLE',
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertNoConflictingLease(
        Apartment $apartment,
        ?string $exceptAgreementId = null,
    ): void {
        if ($this->hasConflictingLease($apartment, $exceptAgreementId)) {
            throw new BusinessRuleException(
                'This unit already has a draft or active rental agreement.',
                'APARTMENT_LEASE_CONFLICT',
            );
        }
    }

    public function hasConflictingLease(
        Apartment $apartment,
        ?string $exceptAgreementId = null,
    ): bool {
        return Agreement::query()
            ->where('apartment_id', $apartment->id)
            ->whereIn('status', self::LEASE_BLOCKING_STATUSES)
            ->when(
                $exceptAgreementId,
                fn ($q) => $q->where('id', '!=', $exceptAgreementId),
            )
            ->exists();
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertCanActivate(Apartment $apartment): void
    {
        if (! in_array($apartment->inventory_status, [
            Apartment::STATUS_AVAILABLE,
            Apartment::STATUS_RESERVED,
            Apartment::STATUS_UNDER_CONTRACT,
        ], true)) {
            throw new BusinessRuleException(
                'Apartment is not available for lease activation.',
                'APARTMENT_NOT_AVAILABLE',
            );
        }
    }

    public function markReserved(Apartment $apartment): void
    {
        if ($apartment->inventory_status === Apartment::STATUS_AVAILABLE) {
            $apartment->update([
                'inventory_status' => Apartment::STATUS_RESERVED,
            ]);
        }
    }

    public function occupy(Apartment $apartment): void
    {
        $apartment->update([
            'inventory_status' => Apartment::STATUS_OCCUPIED,
        ]);
    }

    public function release(Apartment $apartment): void
    {
        $apartment->update([
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertCanDelete(Apartment $apartment): void
    {
        if ($this->hasConflictingLease($apartment)) {
            throw new BusinessRuleException(
                'Cannot delete a unit with draft or active rental agreements.',
                'APARTMENT_HAS_LEASES',
            );
        }
    }

    /**
     * Prevent manual "available" while an active agreement exists.
     *
     * @throws BusinessRuleException
     */
    public function assertInventoryStatusChangeAllowed(
        Apartment $apartment,
        string $newStatus,
    ): void {
        if (
            $newStatus === Apartment::STATUS_AVAILABLE
            && Agreement::query()
                ->where('apartment_id', $apartment->id)
                ->where('status', Agreement::STATUS_ACTIVE)
                ->exists()
        ) {
            throw new BusinessRuleException(
                'Cannot mark unit as available while an active rental agreement exists. Terminate the agreement first.',
                'APARTMENT_ACTIVE_LEASE',
            );
        }

        if (
            $newStatus === Apartment::STATUS_OCCUPIED
            && ! Agreement::query()
                ->where('apartment_id', $apartment->id)
                ->where('status', Agreement::STATUS_ACTIVE)
                ->exists()
        ) {
            throw new BusinessRuleException(
                'Cannot mark unit as occupied without an active rental agreement.',
                'APARTMENT_NO_ACTIVE_LEASE',
            );
        }
    }
}
