<?php

namespace App\Services\Sales;

use Carbon\Carbon;

class SalePaymentPlanService
{
    public function financedAmount(float $salePrice, float $downPayment): float
    {
        return max(0, round($salePrice - $downPayment, 2));
    }

    public function resolveEndDate(
        string $startDate,
        ?string $endDate,
        ?int $years,
        ?int $months,
    ): ?string {
        if ($endDate) {
            return $endDate;
        }

        $years = max(0, (int) ($years ?? 0));
        $months = max(0, (int) ($months ?? 0));

        if ($years === 0 && $months === 0) {
            return null;
        }

        return Carbon::parse($startDate)
            ->addYears($years)
            ->addMonths($months)
            ->toDateString();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{years: int, months: int}
     */
    public function resolveDuration(array $data): array
    {
        if (isset($data['plan_duration_years']) || isset($data['plan_duration_months'])) {
            return [
                'years' => max(0, (int) ($data['plan_duration_years'] ?? 0)),
                'months' => max(0, (int) ($data['plan_duration_months'] ?? 0)),
            ];
        }

        if (! empty($data['installment_months'])) {
            return [
                'years' => 0,
                'months' => max(1, (int) $data['installment_months']),
            ];
        }

        return ['years' => 0, 'months' => 0];
    }
}
