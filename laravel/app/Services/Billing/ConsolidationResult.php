<?php

namespace App\Services\Billing;

use App\Models\MonthlyInvoice;

final class ConsolidationResult
{
    public const OUTCOME_CREATED = 'created';

    public const OUTCOME_SKIPPED_NO_ITEMS = 'no_items';

    public const OUTCOME_SKIPPED_ALREADY_EXISTS = 'already_exists';

    public const OUTCOME_APPENDED = 'appended';

    public function __construct(
        public readonly ?MonthlyInvoice $invoice,
        public readonly string $outcome,
        public readonly ?string $agreementNumber = null,
    ) {}

    public function wasCreated(): bool
    {
        return $this->outcome === self::OUTCOME_CREATED;
    }

    public function wasAppended(): bool
    {
        return $this->outcome === self::OUTCOME_APPENDED;
    }

    public function wasSuccessful(): bool
    {
        return $this->wasCreated() || $this->wasAppended();
    }
}
