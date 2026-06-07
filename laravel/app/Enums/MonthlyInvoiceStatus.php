<?php

namespace App\Enums;

enum MonthlyInvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Finalized = 'finalized';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function isVoidable(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Issued,
            self::Finalized,
            self::PartiallyPaid,
            self::Overdue,
        ], true);
    }

    public function isIssuable(): bool
    {
        return $this === self::Draft;
    }
}
