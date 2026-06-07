<?php

namespace App\Services\Billing\DTOs;

class ChargeCalculationResult
{
    public function __construct(

        public readonly float $amount,

        public readonly float $subtotal,

        public readonly float $taxAmount,

        public readonly float $totalAmount,

        public readonly array $breakdown = [],
    ) {}
}