<?php

namespace App\Services\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Payment;
use App\Models\SaleAgreement;
use App\Models\SaleDepositApplication;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleDepositService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    /**
     * @return array{
     *   reservation_deposit: float,
     *   applied: float,
     *   available: float,
     *   has_reservation: bool,
     *   reservation_number: string|null,
     * }
     */
    public function summary(string $companyId, string $saleAgreementId): array
    {
        $reservation = $this->linkedReservation($companyId, $saleAgreementId);

        if (! $reservation || ! $reservation->deposit_payment_id) {
            return [
                'reservation_deposit' => 0.0,
                'applied' => 0.0,
                'available' => 0.0,
                'has_reservation' => false,
                'reservation_number' => null,
            ];
        }

        $reservation->loadMissing('depositPayment');

        $depositTotal = round((float) ($reservation->depositPayment?->amount ?? 0), 2);
        $applied = round((float) SaleDepositApplication::query()
            ->where('company_id', $companyId)
            ->where('sale_agreement_id', $saleAgreementId)
            ->sum('amount'), 2);

        return [
            'reservation_deposit' => $depositTotal,
            'applied' => $applied,
            'available' => round(max(0, $depositTotal - $applied), 2),
            'has_reservation' => true,
            'reservation_number' => $reservation->reservation_number,
        ];
    }

    /**
     * @param  array{amount?: float|string, notes?: string}  $data
     */
    public function applyToSaleContract(User $actor, string $saleAgreementId, array $data = []): SaleDepositApplication
    {
        return DB::transaction(function () use ($actor, $saleAgreementId, $data) {
            $sale = SaleAgreement::query()
                ->with(['agreement', 'paymentAllocations', 'depositApplications'])
                ->whereHas(
                    'agreement',
                    fn ($query) => $query
                        ->where('company_id', $actor->company_id)
                        ->where('id', $saleAgreementId),
                )
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->agreement->status !== Agreement::STATUS_ACTIVE) {
                throw new BusinessRuleException(
                    'Reservation deposit can only be applied to active sale contracts.',
                    'SALE_NOT_ACTIVE',
                );
            }

            $reservation = $this->linkedReservation($actor->company_id, $saleAgreementId);

            if (! $reservation || ! $reservation->deposit_payment_id) {
                throw ValidationException::withMessages([
                    'sale_agreement_id' => ['This sale contract has no linked reservation deposit.'],
                ]);
            }

            $reservation->loadMissing('depositPayment');
            $depositPayment = $reservation->depositPayment;

            if (! $depositPayment) {
                throw ValidationException::withMessages([
                    'sale_agreement_id' => ['Reservation deposit payment is missing.'],
                ]);
            }

            $summary = $this->summary($actor->company_id, $saleAgreementId);
            $available = $summary['available'];
            $balanceDue = $sale->balanceDue();

            if ($available <= 0.009) {
                throw ValidationException::withMessages([
                    'amount' => ['No reservation deposit balance remains to apply.'],
                ]);
            }

            if ($balanceDue <= 0.009) {
                throw ValidationException::withMessages([
                    'amount' => ['Sale contract has no outstanding balance.'],
                ]);
            }

            $requested = isset($data['amount'])
                ? round((float) $data['amount'], 2)
                : round(min($available, $balanceDue), 2);

            if ($requested <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Amount must be greater than zero.'],
                ]);
            }

            if ($requested > $available + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => [sprintf(
                        'Application exceeds available reservation deposit ($%s).',
                        number_format($available, 2),
                    )],
                ]);
            }

            $amount = round(min($requested, $balanceDue), 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Amount must be greater than zero.'],
                ]);
            }

            $application = SaleDepositApplication::query()->create([
                'company_id' => $actor->company_id,
                'sale_agreement_id' => $sale->id,
                'sale_reservation_id' => $reservation->id,
                'deposit_payment_id' => $depositPayment->id,
                'amount' => $amount,
                'applied_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->journalEntryService->postSaleDepositApplication($application, $actor->id);

            return $application->load(['saleAgreement.agreement', 'saleReservation', 'depositPayment']);
        });
    }

    private function linkedReservation(string $companyId, string $saleAgreementId): ?SaleReservation
    {
        return SaleReservation::query()
            ->where('company_id', $companyId)
            ->where('converted_agreement_id', $saleAgreementId)
            ->whereNotNull('deposit_payment_id')
            ->first();
    }
}
