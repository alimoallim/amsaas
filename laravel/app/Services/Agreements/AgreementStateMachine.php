<?php

namespace App\Services\Agreements;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\User;

class AgreementStateMachine
{
    /**
     * Allowed status transitions (from => [to, ...]).
     *
     * Aligns with master plan: DRAFT → PENDING → ACTIVE → TERMINATED / EXPIRED
     * (pending_approval = PENDING, approved precedes activation).
     */
    private const TRANSITIONS = [
        Agreement::STATUS_DRAFT => [
            Agreement::STATUS_PENDING_APPROVAL,
            Agreement::STATUS_APPROVED,
            Agreement::STATUS_ACTIVE,
            Agreement::STATUS_CANCELLED,
        ],
        Agreement::STATUS_PENDING_APPROVAL => [
            Agreement::STATUS_APPROVED,
            Agreement::STATUS_CANCELLED,
            Agreement::STATUS_DRAFT,
        ],
        Agreement::STATUS_APPROVED => [
            Agreement::STATUS_ACTIVE,
            Agreement::STATUS_CANCELLED,
        ],
        Agreement::STATUS_ACTIVE => [
            Agreement::STATUS_TERMINATED,
            Agreement::STATUS_EXPIRED,
            Agreement::STATUS_COMPLETED,
            Agreement::STATUS_DEFAULTED,
        ],
        Agreement::STATUS_RENEWAL_PENDING => [
            Agreement::STATUS_RENEWED,
            Agreement::STATUS_CANCELLED,
        ],
        Agreement::STATUS_RENEWED => [
            Agreement::STATUS_ACTIVE,
        ],
    ];

    public function canTransition(Agreement $agreement, string $toStatus): bool
    {
        $allowed = self::TRANSITIONS[$agreement->status] ?? [];

        return in_array($toStatus, $allowed, true);
    }

    /**
     * @throws BusinessRuleException
     */
    public function transition(
        Agreement $agreement,
        string $toStatus,
        ?User $actor = null,
        array $extraAttributes = [],
    ): Agreement {
        if (! $this->canTransition($agreement, $toStatus)) {
            throw new BusinessRuleException(
                "Invalid agreement transition from [{$agreement->status}] to [{$toStatus}].",
                'AGREEMENT_INVALID_TRANSITION',
            );
        }

        $attributes = array_merge(['status' => $toStatus], $extraAttributes);

        if ($toStatus === Agreement::STATUS_ACTIVE && $actor) {
            $attributes['approved_at'] = $attributes['approved_at'] ?? now();
            $attributes['approved_by'] = $attributes['approved_by'] ?? $actor->id;
        }

        if ($toStatus === Agreement::STATUS_TERMINATED && $actor) {
            $attributes['terminated_at'] = $attributes['terminated_at'] ?? now();
            $attributes['terminated_by'] = $attributes['terminated_by'] ?? $actor->id;
        }

        $agreement->update($attributes);

        return $agreement->fresh();
    }

    /**
     * @throws BusinessRuleException
     */
    public function ensureBillable(Agreement $agreement): void
    {
        if (! $agreement->isCurrentlyActive()) {
            throw new BusinessRuleException(
                'Billing requires an ACTIVE agreement.',
                'AGREEMENT_NOT_ACTIVE',
            );
        }
    }
}
