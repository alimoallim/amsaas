<?php

namespace App\Services\Agreements;

use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ProrateRentService
{
    /**
     * Prorate monthly rent for a partial period using BCMath.
     *
     * @param  string  $monthlyRent  Decimal string (e.g. "1000.0000")
     * @return string Prorated amount with scale 4
     */
    public function prorate(
        string $monthlyRent,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        CarbonInterface $occupancyStart,
        ?CarbonInterface $occupancyEnd = null,
    ): string {
        $periodStart = Carbon::parse($periodStart)->startOfDay();
        $periodEnd = Carbon::parse($periodEnd)->startOfDay();
        $occupancyStart = Carbon::parse($occupancyStart)->startOfDay();
        $occupancyEnd = $occupancyEnd
            ? Carbon::parse($occupancyEnd)->startOfDay()
            : $periodEnd->copy();

        if ($occupancyStart->gt($periodEnd) || $occupancyEnd->lt($periodStart)) {
            return Money::zero();
        }

        $effectiveStart = $occupancyStart->greaterThan($periodStart)
            ? $occupancyStart
            : $periodStart;

        $effectiveEnd = $occupancyEnd->lessThan($periodEnd)
            ? $occupancyEnd
            : $periodEnd;

        if ($effectiveStart->gt($effectiveEnd)) {
            return Money::zero();
        }

        $daysInPeriod = (int) $periodStart->diffInDays($periodEnd) + 1;
        $daysOccupied = (int) $effectiveStart->diffInDays($effectiveEnd) + 1;

        if ($daysInPeriod <= 0) {
            return Money::zero();
        }

        $dailyRate = Money::div($monthlyRent, (string) $daysInPeriod);

        return Money::mul($dailyRate, (string) $daysOccupied);
    }
}
