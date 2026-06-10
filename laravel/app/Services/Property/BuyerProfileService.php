<?php

namespace App\Services\Property;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Buyer;
use App\Models\Tenant;

class BuyerProfileService
{
    /**
     * @throws BusinessRuleException
     */
    public function assertTenantLinkValid(Buyer $buyer, ?string $tenantId, string $companyId): void
    {
        if ($tenantId === null) {
            return;
        }

        $tenant = Tenant::query()
            ->where('company_id', $companyId)
            ->find($tenantId);

        if (! $tenant) {
            throw new BusinessRuleException(
                'Linked tenant record was not found in your company.',
                'BUYER_TENANT_NOT_FOUND',
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertCanDelete(Buyer $buyer): void
    {
        $hasSaleAgreements = Agreement::query()
            ->where('buyer_id', $buyer->id)
            ->where('agreement_type', Agreement::TYPE_SALE)
            ->whereNotIn('status', [
                Agreement::STATUS_CANCELLED,
                Agreement::STATUS_COMPLETED,
            ])
            ->exists();

        if ($hasSaleAgreements) {
            throw new BusinessRuleException(
                'Cannot delete a buyer with open sale agreements.',
                'BUYER_HAS_SALE_AGREEMENTS',
            );
        }

        if ($buyer->payments()->exists()) {
            throw new BusinessRuleException(
                'Cannot delete a buyer with payment history.',
                'BUYER_HAS_PAYMENTS',
            );
        }
    }
}
