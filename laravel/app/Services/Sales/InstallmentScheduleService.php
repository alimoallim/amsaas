<?php

namespace App\Services\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\InstallmentSchedule;
use App\Models\SaleAgreement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstallmentScheduleService
{
    /**
     * Generate the immutable instalment schedule for an active instalment contract.
     *
     * @return Collection<int, InstallmentSchedule>
     */
    public function generate(User $actor, string $agreementId): Collection
    {
        return DB::transaction(function () use ($actor, $agreementId) {
            $sale = SaleAgreement::query()
                ->with('agreement')
                ->where('id', $agreementId)
                ->whereHas(
                    'agreement',
                    fn ($query) => $query->where('company_id', $actor->company_id),
                )
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanGenerate($sale);

            $agreement = $sale->agreement;
            $months = (int) $sale->installment_months;
            $remaining = round((float) $sale->sale_price - (float) ($sale->down_payment ?? 0), 2);
            $baseAmount = round($remaining / $months, 2);
            $startDate = Carbon::parse($agreement->start_date ?? now()->toDateString());

            $rows = [];
            $allocated = 0.0;

            for ($number = 1; $number <= $months; $number++) {
                $amount = $number === $months
                    ? round($remaining - $allocated, 2)
                    : $baseAmount;

                $allocated = round($allocated + $amount, 2);

                $rows[] = InstallmentSchedule::create([
                    'company_id' => $actor->company_id,
                    'sale_agreement_id' => $sale->id,
                    'installment_number' => $number,
                    'due_date' => $startDate->copy()->addMonths($number)->toDateString(),
                    'amount' => $amount,
                    'principal' => $amount,
                    'interest' => 0,
                    'paid_amount' => 0,
                    'status' => InstallmentSchedule::STATUS_PENDING,
                ]);
            }

            $this->assertScheduleTotals($sale, collect($rows));

            return collect($rows);
        });
    }

    public function hasSchedule(SaleAgreement $sale): bool
    {
        if ($sale->relationLoaded('installmentSchedules')) {
            return $sale->installmentSchedules->isNotEmpty();
        }

        return $sale->installmentSchedules()->exists();
    }

    /**
     * @throws BusinessRuleException
     */
    private function assertCanGenerate(SaleAgreement $sale): void
    {
        if (! $sale->is_installment_sale) {
            throw new BusinessRuleException(
                'Instalment schedules can only be generated for instalment sale contracts.',
                'SALE_NOT_INSTALLMENT',
            );
        }

        $status = $sale->agreement?->status;
        if (! in_array($status, [Agreement::STATUS_ACTIVE, Agreement::STATUS_COMPLETED], true)) {
            throw new BusinessRuleException(
                'Instalment schedule can only be generated for active sale contracts.',
                'SALE_NOT_ACTIVE',
            );
        }

        if ($this->hasSchedule($sale)) {
            throw new BusinessRuleException(
                'An instalment schedule already exists for this contract.',
                'INSTALLMENT_SCHEDULE_EXISTS',
            );
        }

        if ((int) $sale->installment_months < 1) {
            throw new BusinessRuleException(
                'Instalment contract is missing a valid instalment count.',
                'INSTALLMENT_MONTHS_INVALID',
            );
        }

        $remaining = (float) $sale->sale_price - (float) ($sale->down_payment ?? 0);
        if ($remaining <= 0) {
            throw new BusinessRuleException(
                'Nothing remains to schedule after the down payment.',
                'INSTALLMENT_NOTHING_TO_SCHEDULE',
            );
        }
    }

    /**
     * @param  Collection<int, InstallmentSchedule>  $rows
     *
     * @throws BusinessRuleException
     */
    private function assertScheduleTotals(SaleAgreement $sale, Collection $rows): void
    {
        $expected = round((float) $sale->sale_price - (float) ($sale->down_payment ?? 0), 2);
        $actual = round((float) $rows->sum('amount'), 2);

        if (abs($expected - $actual) > 0.02) {
            throw new BusinessRuleException(
                "Generated schedule total ({$actual}) does not match contract balance ({$expected}).",
                'INSTALLMENT_SCHEDULE_TOTAL_MISMATCH',
            );
        }
    }
}
