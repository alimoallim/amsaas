<?php

namespace Tests\Unit\Agreements;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Services\Agreements\AgreementStateMachine;
use Tests\TestCase;

class AgreementStateMachineTest extends TestCase
{
    private AgreementStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new AgreementStateMachine;
    }

    public function test_it_allows_draft_to_pending_approval(): void
    {
        $agreement = new Agreement(['status' => Agreement::STATUS_DRAFT]);

        $this->assertTrue(
            $this->machine->canTransition($agreement, Agreement::STATUS_PENDING_APPROVAL)
        );
    }

    public function test_it_rejects_active_to_draft(): void
    {
        $agreement = new Agreement(['status' => Agreement::STATUS_ACTIVE]);

        $this->assertFalse(
            $this->machine->canTransition($agreement, Agreement::STATUS_DRAFT)
        );
    }

    public function test_it_allows_draft_to_active_for_direct_activation(): void
    {
        $agreement = new Agreement(['status' => Agreement::STATUS_DRAFT]);

        $this->assertTrue(
            $this->machine->canTransition($agreement, Agreement::STATUS_ACTIVE)
        );
    }

    public function test_it_rejects_draft_to_completed(): void
    {
        $agreement = new Agreement(['status' => Agreement::STATUS_DRAFT]);

        $this->expectException(BusinessRuleException::class);

        $this->machine->transition($agreement, Agreement::STATUS_COMPLETED);
    }

    public function test_ensure_billable_requires_active(): void
    {
        $agreement = new Agreement(['status' => Agreement::STATUS_DRAFT]);

        $this->expectException(BusinessRuleException::class);

        $this->machine->ensureBillable($agreement);
    }
}
