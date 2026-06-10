<?php

namespace App\Services\Property;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\ApartmentInventoryStatusLog;
use Illuminate\Support\Facades\Auth;

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
    public function assertCanSell(Apartment $apartment): void
    {
        if (! $apartment->canBeSold()) {
            throw new BusinessRuleException(
                'Apartment is not listed for sale or is not in a sellable inventory status.',
                'APARTMENT_NOT_SELLABLE',
            );
        }

        if ($apartment->inventory_status === Apartment::STATUS_OCCUPIED) {
            throw new BusinessRuleException(
                'Cannot sell a unit that is currently occupied.',
                'APARTMENT_OCCUPIED',
            );
        }

        if ($this->hasConflictingLease($apartment)) {
            throw new BusinessRuleException(
                'Cannot sell a unit with an active or draft rental agreement.',
                'APARTMENT_ACTIVE_LEASE',
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertCanReserveForSale(Apartment $apartment): void
    {
        $this->assertCanSell($apartment);

        if (! in_array($apartment->inventory_status, [
            Apartment::STATUS_AVAILABLE,
        ], true)) {
            throw new BusinessRuleException(
                'Only available units can be reserved for sale.',
                'APARTMENT_NOT_AVAILABLE_FOR_SALE',
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
            ->where('agreement_type', Agreement::TYPE_RENTAL)
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

    public function markReserved(Apartment $apartment, ?string $reason = null): void
    {
        if ($apartment->inventory_status === Apartment::STATUS_AVAILABLE) {
            $this->transitionStatus($apartment, Apartment::STATUS_RESERVED, $reason ?? 'Rental draft created');
        }
    }

    public function markReservedForSale(Apartment $apartment, ?string $reason = null): void
    {
        $this->assertCanReserveForSale($apartment);
        $this->transitionStatus($apartment, Apartment::STATUS_RESERVED, $reason ?? 'Sale reservation');
    }

    public function markUnderContract(Apartment $apartment, ?string $reason = null): void
    {
        $this->assertCanSell($apartment);
        $this->transitionStatus($apartment, Apartment::STATUS_UNDER_CONTRACT, $reason ?? 'Sale contract executed');
    }

    public function markSold(Apartment $apartment, ?string $reason = null): void
    {
        $this->transitionStatus($apartment, Apartment::STATUS_SOLD, $reason ?? 'Ownership transferred');
    }

    public function occupy(Apartment $apartment, ?string $reason = null): void
    {
        $this->transitionStatus($apartment, Apartment::STATUS_OCCUPIED, $reason ?? 'Lease activated');
    }

    public function release(Apartment $apartment, ?string $reason = null): void
    {
        $this->transitionStatus($apartment, Apartment::STATUS_AVAILABLE, $reason ?? 'Unit released');
    }

    /**
     * Optimistic-lock status transition with audit log.
     *
     * @throws BusinessRuleException
     */
    public function transitionStatus(
        Apartment $apartment,
        string $newStatus,
        ?string $reason = null,
    ): void {
        if ($apartment->inventory_status === $newStatus) {
            return;
        }

        $fromStatus = $apartment->inventory_status;
        $expectedVersion = (int) $apartment->lock_version;

        $updated = Apartment::query()
            ->where('id', $apartment->id)
            ->where('lock_version', $expectedVersion)
            ->update([
                'inventory_status' => $newStatus,
                'lock_version' => $expectedVersion + 1,
            ]);

        if ($updated === 0) {
            throw new BusinessRuleException(
                'Inventory was updated by another process. Please refresh and try again.',
                'INVENTORY_VERSION_CONFLICT',
            );
        }

        $apartment->refresh();

        ApartmentInventoryStatusLog::create([
            'company_id' => $apartment->company_id,
            'apartment_id' => $apartment->id,
            'from_status' => $fromStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => Auth::id(),
            'created_at' => now(),
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

        if (
            in_array($newStatus, [Apartment::STATUS_UNDER_CONTRACT, Apartment::STATUS_SOLD], true)
            && in_array($apartment->listing_type, [Apartment::LISTING_TYPE_SALE, Apartment::LISTING_TYPE_HYBRID], true)
        ) {
            $this->assertCanSell($apartment);
        }
    }
}
