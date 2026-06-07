<?php

namespace App\Services\Property;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Tenant;

class TenantLeaseEligibilityService
{
    /**
     * @throws BusinessRuleException
     */
    public function assertCanSignLease(Tenant $tenant): void
    {
        if ($tenant->status === 'blacklisted') {
            throw new BusinessRuleException(
                'Blacklisted tenants cannot be assigned to rental agreements.',
                'TENANT_BLACKLISTED',
            );
        }

        if ($tenant->status === 'inactive') {
            throw new BusinessRuleException(
                'Inactive tenants cannot be assigned to new rental agreements.',
                'TENANT_INACTIVE',
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertCanDelete(Tenant $tenant): void
    {
        $hasBlockingAgreement = Agreement::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Agreement::STATUS_ACTIVE,
                Agreement::STATUS_APPROVED,
                Agreement::STATUS_PENDING_APPROVAL,
                Agreement::STATUS_DRAFT,
            ])
            ->exists();

        if ($hasBlockingAgreement) {
            throw new BusinessRuleException(
                'Cannot delete a tenant linked to draft or active rental agreements.',
                'TENANT_HAS_LEASES',
            );
        }
    }
}
