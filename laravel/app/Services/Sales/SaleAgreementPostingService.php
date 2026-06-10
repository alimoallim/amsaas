<?php

namespace App\Services\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Payment;
use App\Models\SaleAgreement;
use App\Models\SalePaymentAllocation;
use App\Models\User;
use App\Services\Agreements\AgreementStateMachine;
use App\Services\Accounting\JournalEntryService;
use App\Services\PaymentService;
use App\Services\Property\ApartmentInventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleAgreementPostingService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly AgreementStateMachine $stateMachine,
        private readonly ApartmentInventoryService $inventory,
        private readonly OwnershipTransferService $ownershipTransfers,
        private readonly SaleDepositService $saleDeposits,
    ) {}

    /**
     * @param  array{amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $data
     * @return array{sale: SaleAgreement, payment: Payment, completed: bool, message: string}
     */
    public function recordPayment(User $actor, string $agreementId, array $data): array
    {
        return DB::transaction(function () use ($actor, $agreementId, $data) {
            $sale = $this->resolveActiveSale($actor, $agreementId);
            $agreement = $sale->agreement;
            $buyerId = $agreement->buyer_id;

            if (! $buyerId) {
                throw new BusinessRuleException(
                    'Sale contract has no linked buyer.',
                    'SALE_NO_BUYER',
                );
            }

            $balanceDue = $sale->balanceDue();
            if ($balanceDue <= 0.009) {
                throw new BusinessRuleException(
                    'This sale contract is already fully paid.',
                    'SALE_ALREADY_PAID',
                );
            }

            $requested = round((float) $data['amount'], 2);
            $amount = round(min($requested, $balanceDue), 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount must be greater than zero.'],
                ]);
            }

            $payment = $this->payments->recordBuyerPayment($actor, [
                'buyer_id' => $buyerId,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $allocation = SalePaymentAllocation::create([
                'company_id' => $actor->company_id,
                'payment_id' => $payment->id,
                'sale_agreement_id' => $sale->id,
                'amount_allocated' => $amount,
            ]);

            app(JournalEntryService::class)->postSalePaymentAllocation(
                $allocation,
                $actor->id,
            );

            $sale->load('paymentAllocations');
            $completed = false;
            $message = 'Payment recorded against sale contract.';

            if ($sale->balanceDue() <= 0.009) {
                $this->completeSale($actor, $sale);
                $completed = true;
                $message = $sale->isPaymentPlan()
                    ? 'Payment recorded. Payment plan completed — unit marked as sold.'
                    : 'Payment recorded. Cash sale completed — unit marked as sold.';
            } elseif ($requested > $balanceDue + 0.009) {
                $message = sprintf(
                    'Payment recorded ($%s). Amount was capped to outstanding balance of $%s.',
                    number_format($amount, 2),
                    number_format($balanceDue, 2),
                );
            }

            return [
                'sale' => $this->reloadSale($sale->id),
                'payment' => $payment,
                'completed' => $completed,
                'message' => $message,
            ];
        });
    }

    /**
     * @param  array{amount?: float|string, notes?: string}  $data
     * @return array{sale: SaleAgreement, application: \App\Models\SaleDepositApplication, completed: bool, message: string}
     */
    public function applyReservationDeposit(User $actor, string $agreementId, array $data = []): array
    {
        return DB::transaction(function () use ($actor, $agreementId, $data) {
            $application = $this->saleDeposits->applyToSaleContract($actor, $agreementId, $data);

            $sale = $this->reloadSale($agreementId);
            $completed = false;
            $message = sprintf(
                'Applied $%s of reservation deposit to sale contract.',
                number_format((float) $application->amount, 2),
            );

            if ($sale->balanceDue() <= 0.009) {
                $this->completeSale($actor, $sale);
                $completed = true;
                $message = sprintf(
                    'Applied $%s reservation deposit. Sale contract is now fully paid and completed.',
                    number_format((float) $application->amount, 2),
                );
                $sale = $this->reloadSale($agreementId);
            }

            return [
                'sale' => $sale,
                'application' => $application,
                'completed' => $completed,
                'message' => $message,
            ];
        });
    }

    /**
     * @deprecated Use recordPayment — payment plans collect flexibly against running balance.
     *
     * @param  array{installment_schedule_id?: string, amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $data
     * @return array{sale: SaleAgreement, payment: Payment, completed: bool, message: string}
     */
    public function recordInstallmentPayment(User $actor, string $agreementId, array $data): array
    {
        $result = $this->recordPayment($actor, $agreementId, $data);

        return [
            'sale' => $result['sale'],
            'payment' => $result['payment'],
            'completed' => $result['completed'],
            'message' => $result['message'],
        ];
    }

    private function completeSale(User $actor, SaleAgreement $sale): void
    {
        $agreement = $sale->agreement;
        $apartment = Apartment::query()
            ->where('id', $agreement->apartment_id)
            ->lockForUpdate()
            ->firstOrFail();

        $this->stateMachine->transition(
            $agreement,
            Agreement::STATUS_COMPLETED,
            $actor,
            ['updated_by' => $actor->id],
        );

        $this->inventory->markSold(
            $apartment,
            sprintf('Sale %s completed', $agreement->agreement_number),
        );

        $this->ownershipTransfers->onSaleCompleted(
            $actor,
            $sale->fresh(['agreement.apartment.building', 'agreement.buyer']),
        );
    }

    private function resolveActiveSale(User $actor, string $agreementId): SaleAgreement
    {
        $sale = SaleAgreement::query()
            ->with(['agreement', 'paymentAllocations'])
            ->whereHas(
                'agreement',
                fn ($query) => $query
                    ->where('company_id', $actor->company_id)
                    ->where('id', $agreementId),
            )
            ->lockForUpdate()
            ->firstOrFail();

        if ($sale->agreement->status !== Agreement::STATUS_ACTIVE) {
            throw new BusinessRuleException(
                'Payments can only be recorded against active sale contracts.',
                'SALE_NOT_ACTIVE',
            );
        }

        return $sale;
    }

    private function reloadSale(string $id): SaleAgreement
    {
        return SaleAgreement::query()
            ->with([
                'agreement.apartment.building',
                'agreement.buyer',
                'paymentAllocations.payment',
                'depositApplications',
                'ownershipApprovals.approvedBy',
            ])
            ->findOrFail($id);
    }
}
