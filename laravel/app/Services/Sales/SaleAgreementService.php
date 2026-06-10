<?php

namespace App\Services\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\SaleAgreement;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\AgreementNumberService;
use App\Services\Agreements\AgreementStateMachine;
use App\Services\Property\ApartmentInventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleAgreementService
{
    /** @var list<string> */
    public const SALE_BLOCKING_STATUSES = [
        Agreement::STATUS_DRAFT,
        Agreement::STATUS_PENDING_APPROVAL,
        Agreement::STATUS_APPROVED,
        Agreement::STATUS_ACTIVE,
    ];

    public function __construct(
        private readonly AgreementStateMachine $stateMachine,
        private readonly ApartmentInventoryService $inventory,
        private readonly SalePaymentPlanService $paymentPlans,
    ) {}

    /**
     * @param  array{
     *   sale_reservation_id?: string,
     *   apartment_id?: string,
     *   buyer_id?: string,
     *   sale_price: float|string,
     *   down_payment?: float|string,
     *   is_installment_sale?: bool,
     *   installment_months?: int,
     *   contract_date?: string,
     *   signed_at?: string,
     *   notes?: string,
     *   special_terms?: string,
     *   broker_name?: string,
     *   broker_commission?: float|string,
     *   execute?: bool,
     * }  $data
     */
    public function create(User $actor, array $data): SaleAgreement
    {
        return DB::transaction(function () use ($actor, $data) {
            [$apartment, $buyer, $reservation] = $this->resolveParties($actor, $data);

            $apartment = Apartment::query()
                ->where('id', $apartment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->inventory->assertCanSell($apartment);
            $this->assertNoConflictingSaleContract($apartment);

            $salePrice = round((float) $data['sale_price'], 2);
            $downPayment = round((float) ($data['down_payment'] ?? 0), 2);
            $this->assertFinancials($salePrice, $downPayment, $data);

            $isPaymentPlan = (bool) ($data['is_payment_plan'] ?? $data['is_installment_sale'] ?? false);
            $duration = $this->paymentPlans->resolveDuration($data);
            $financedAmount = $isPaymentPlan
                ? $this->paymentPlans->financedAmount($salePrice, $downPayment)
                : null;

            $contractDate = $data['contract_date'] ?? $data['start_date'] ?? now()->toDateString();
            $endDate = $isPaymentPlan
                ? $this->paymentPlans->resolveEndDate(
                    $contractDate,
                    $data['agreement_end_date'] ?? $data['end_date'] ?? null,
                    $duration['years'],
                    $duration['months'],
                )
                : null;

            $agreement = Agreement::create([
                'company_id' => $actor->company_id,
                'agreement_number' => AgreementNumberService::allocate(Agreement::TYPE_SALE),
                'agreement_type' => Agreement::TYPE_SALE,
                'apartment_id' => $apartment->id,
                'buyer_id' => $buyer->id,
                'status' => Agreement::STATUS_DRAFT,
                'start_date' => $contractDate,
                'end_date' => $endDate,
                'signed_at' => $data['signed_at'] ?? null,
                'contract_amount' => $salePrice,
                'currency' => strtoupper($apartment->currency ?? 'USD'),
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $sale = SaleAgreement::create([
                'id' => $agreement->id,
                'sale_price' => $salePrice,
                'down_payment' => $downPayment,
                'financed_amount' => $financedAmount,
                'is_installment_sale' => $isPaymentPlan,
                'installment_months' => $isPaymentPlan
                    ? ($duration['years'] * 12) + $duration['months']
                    : null,
                'plan_duration_years' => $isPaymentPlan ? $duration['years'] : null,
                'plan_duration_months' => $isPaymentPlan ? $duration['months'] : null,
                'monthly_installment_amount' => null,
                'broker_name' => $data['broker_name'] ?? null,
                'broker_commission' => isset($data['broker_commission'])
                    ? round((float) $data['broker_commission'], 2)
                    : null,
                'special_terms' => $data['special_terms'] ?? null,
            ]);

            if ($reservation) {
                $this->linkReservation($reservation, $sale);
            }

            if (! empty($data['execute'])) {
                return $this->execute($actor, $sale->id);
            }

            return $this->loadDetail($sale->id, $actor->company_id);
        });
    }

    /**
     * @param  array{
     *   sale_price?: float|string,
     *   down_payment?: float|string,
     *   is_installment_sale?: bool,
     *   is_payment_plan?: bool,
     *   installment_months?: int,
     *   plan_duration_years?: int,
     *   plan_duration_months?: int,
     *   agreement_end_date?: string,
     *   end_date?: string,
     *   contract_date?: string,
     *   signed_at?: string,
     *   notes?: string,
     *   special_terms?: string,
     *   broker_name?: string,
     *   broker_commission?: float|string,
     * }  $data
     */
    public function update(User $actor, string $agreementId, array $data): SaleAgreement
    {
        return DB::transaction(function () use ($actor, $agreementId, $data) {
            $sale = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $sale->agreement;

            if (! $agreement->canBeEdited()) {
                throw new BusinessRuleException(
                    'Executed sale contracts cannot be edited.',
                    'SALE_AGREEMENT_LOCKED',
                );
            }

            $salePrice = array_key_exists('sale_price', $data)
                ? round((float) $data['sale_price'], 2)
                : (float) $sale->sale_price;

            $downPayment = array_key_exists('down_payment', $data)
                ? round((float) $data['down_payment'], 2)
                : (float) ($sale->down_payment ?? 0);

            $isPaymentPlan = array_key_exists('is_payment_plan', $data) || array_key_exists('is_installment_sale', $data)
                ? (bool) ($data['is_payment_plan'] ?? $data['is_installment_sale'] ?? false)
                : (bool) $sale->is_installment_sale;

            $duration = $this->paymentPlans->resolveDuration(
                $isPaymentPlan
                    ? array_merge([
                        'plan_duration_years' => $sale->plan_duration_years,
                        'plan_duration_months' => $sale->plan_duration_months,
                        'installment_months' => $sale->installment_months,
                    ], $data)
                    : $data,
            );

            $this->assertFinancials($salePrice, $downPayment, [
                'is_installment_sale' => $isPaymentPlan,
                'is_payment_plan' => $isPaymentPlan,
                'plan_duration_years' => $duration['years'],
                'plan_duration_months' => $duration['months'],
                'installment_months' => ($duration['years'] * 12) + $duration['months'],
            ]);

            $startDate = $data['contract_date'] ?? $data['start_date'] ?? $agreement->start_date?->toDateString();
            $endDate = $isPaymentPlan
                ? $this->paymentPlans->resolveEndDate(
                    $startDate ?? now()->toDateString(),
                    $data['agreement_end_date'] ?? $data['end_date'] ?? $agreement->end_date?->toDateString(),
                    $duration['years'],
                    $duration['months'],
                )
                : null;

            $agreementUpdates = array_filter([
                'start_date' => $data['contract_date'] ?? $data['start_date'] ?? null,
                'end_date' => $endDate,
                'signed_at' => $data['signed_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'contract_amount' => $salePrice,
                'updated_by' => $actor->id,
            ], fn ($value) => $value !== null);

            if ($agreementUpdates !== []) {
                $agreement->update($agreementUpdates);
            }

            $sale->update([
                'sale_price' => $salePrice,
                'down_payment' => $downPayment,
                'financed_amount' => $isPaymentPlan
                    ? $this->paymentPlans->financedAmount($salePrice, $downPayment)
                    : null,
                'is_installment_sale' => $isPaymentPlan,
                'installment_months' => $isPaymentPlan
                    ? ($duration['years'] * 12) + $duration['months']
                    : null,
                'plan_duration_years' => $isPaymentPlan ? $duration['years'] : null,
                'plan_duration_months' => $isPaymentPlan ? $duration['months'] : null,
                'monthly_installment_amount' => null,
                'broker_name' => $data['broker_name'] ?? $sale->broker_name,
                'broker_commission' => array_key_exists('broker_commission', $data)
                    ? round((float) $data['broker_commission'], 2)
                    : $sale->broker_commission,
                'special_terms' => $data['special_terms'] ?? $sale->special_terms,
            ]);

            return $this->loadDetail($sale->id, $actor->company_id);
        });
    }

    public function execute(User $actor, string $agreementId): SaleAgreement
    {
        return DB::transaction(function () use ($actor, $agreementId) {
            $sale = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $sale->agreement;
            $apartment = Apartment::query()
                ->where('id', $agreement->apartment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($agreement->status !== Agreement::STATUS_DRAFT) {
                throw new BusinessRuleException(
                    'Only draft sale contracts can be executed.',
                    'SALE_AGREEMENT_NOT_DRAFT',
                );
            }

            $this->inventory->assertCanSell($apartment);
            $this->assertNoConflictingSaleContract($apartment, $agreement->id);

            $this->stateMachine->transition(
                $agreement,
                Agreement::STATUS_ACTIVE,
                $actor,
                [
                    'signed_at' => $agreement->signed_at ?? now(),
                    'approved_at' => now(),
                    'approved_by' => $actor->id,
                    'updated_by' => $actor->id,
                ],
            );

            $this->inventory->markUnderContract(
                $apartment,
                "Sale contract {$agreement->agreement_number} executed",
            );

            $this->convertLinkedReservation($sale);

            return $this->loadDetail($sale->id, $actor->company_id);
        });
    }

    public function cancel(User $actor, string $agreementId, ?string $reason = null): SaleAgreement
    {
        return DB::transaction(function () use ($actor, $agreementId, $reason) {
            $sale = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $sale->agreement;

            if ($agreement->status !== Agreement::STATUS_DRAFT) {
                throw new BusinessRuleException(
                    'Only draft sale contracts can be cancelled.',
                    'SALE_AGREEMENT_NOT_DRAFT',
                );
            }

            $this->stateMachine->transition(
                $agreement,
                Agreement::STATUS_CANCELLED,
                $actor,
                [
                    'notes' => trim(($agreement->notes ?? '')."\nCancelled: ".($reason ?? 'Manual cancellation')),
                    'updated_by' => $actor->id,
                ],
            );

            return $this->loadDetail($sale->id, $actor->company_id);
        });
    }

    public function destroy(User $actor, string $agreementId): void
    {
        DB::transaction(function () use ($actor, $agreementId) {
            $sale = $this->resolveForCompany($actor->company_id, $agreementId, lock: true);
            $agreement = $sale->agreement;

            if ($agreement->status !== Agreement::STATUS_DRAFT) {
                throw new BusinessRuleException(
                    'Only draft sale contracts can be deleted.',
                    'SALE_AGREEMENT_NOT_DRAFT',
                );
            }

            $agreement->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Apartment, 1: Buyer, 2: ?SaleReservation}
     */
    private function resolveParties(User $actor, array $data): array
    {
        if (! empty($data['sale_reservation_id'])) {
            $reservation = SaleReservation::query()
                ->where('id', $data['sale_reservation_id'])
                ->where('company_id', $actor->company_id)
                ->with(['apartment', 'buyer'])
                ->firstOrFail();

            if ($reservation->status !== SaleReservation::STATUS_CONFIRMED) {
                throw new BusinessRuleException(
                    'Only confirmed reservations can be converted to a sale contract.',
                    'RESERVATION_NOT_CONFIRMED',
                );
            }

            if ($reservation->converted_agreement_id) {
                throw new BusinessRuleException(
                    'This reservation has already been converted to a contract.',
                    'RESERVATION_ALREADY_CONVERTED',
                );
            }

            return [$reservation->apartment, $reservation->buyer, $reservation];
        }

        if (empty($data['apartment_id']) || empty($data['buyer_id'])) {
            throw ValidationException::withMessages([
                'apartment_id' => ['Apartment and buyer are required when no reservation is provided.'],
            ]);
        }

        $apartment = Apartment::query()
            ->where('id', $data['apartment_id'])
            ->where('company_id', $actor->company_id)
            ->firstOrFail();

        $buyer = Buyer::query()
            ->where('id', $data['buyer_id'])
            ->where('company_id', $actor->company_id)
            ->firstOrFail();

        return [$apartment, $buyer, null];
    }

    private function linkReservation(SaleReservation $reservation, SaleAgreement $sale): void
    {
        if ($reservation->converted_agreement_id) {
            return;
        }

        $reservation->update([
            'converted_agreement_id' => $sale->id,
        ]);
    }

    private function convertLinkedReservation(SaleAgreement $sale): void
    {
        $reservation = SaleReservation::query()
            ->where('converted_agreement_id', $sale->id)
            ->where('status', SaleReservation::STATUS_CONFIRMED)
            ->lockForUpdate()
            ->first();

        if (! $reservation) {
            return;
        }

        $reservation->update([
            'status' => SaleReservation::STATUS_CONVERTED,
        ]);
    }

    private function assertNoConflictingSaleContract(
        Apartment $apartment,
        ?string $exceptAgreementId = null,
    ): void {
        $exists = Agreement::query()
            ->where('apartment_id', $apartment->id)
            ->where('agreement_type', Agreement::TYPE_SALE)
            ->whereIn('status', self::SALE_BLOCKING_STATUSES)
            ->when(
                $exceptAgreementId,
                fn ($q) => $q->where('id', '!=', $exceptAgreementId),
            )
            ->exists();

        if ($exists) {
            throw new BusinessRuleException(
                'This unit already has a draft or active sale contract.',
                'SALE_AGREEMENT_CONFLICT',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertFinancials(float $salePrice, float $downPayment, array $data): void
    {
        if ($salePrice <= 0) {
            throw ValidationException::withMessages([
                'sale_price' => ['Sale price must be greater than zero.'],
            ]);
        }

        if ($downPayment < 0) {
            throw ValidationException::withMessages([
                'down_payment' => ['Down payment cannot be negative.'],
            ]);
        }

        if ($downPayment > $salePrice) {
            throw ValidationException::withMessages([
                'down_payment' => ['Down payment cannot exceed the sale price.'],
            ]);
        }

        $isPaymentPlan = (bool) ($data['is_payment_plan'] ?? $data['is_installment_sale'] ?? false);

        if ($isPaymentPlan) {
            $duration = $this->paymentPlans->resolveDuration($data);
            $totalMonths = ($duration['years'] * 12) + $duration['months'];
            $hasEndDate = ! empty($data['agreement_end_date'] ?? $data['end_date'] ?? null);

            if ($totalMonths < 1 && ! $hasEndDate) {
                throw ValidationException::withMessages([
                    'plan_duration_years' => ['Specify plan duration or an agreement end date for payment plan contracts.'],
                ]);
            }

            if (($salePrice - $downPayment) <= 0) {
                throw ValidationException::withMessages([
                    'down_payment' => ['Down payment must be less than sale price for payment plan contracts.'],
                ]);
            }
        }
    }

    private function resolveForCompany(
        string $companyId,
        string $agreementId,
        bool $lock = false,
    ): SaleAgreement {
        $query = SaleAgreement::query()
            ->where('id', $agreementId)
            ->whereHas(
                'agreement',
                fn ($q) => $q->where('company_id', $companyId),
            )
            ->with(['agreement.apartment.building', 'agreement.buyer']);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    private function loadDetail(string $agreementId, string $companyId): SaleAgreement
    {
        return $this->resolveForCompany($companyId, $agreementId)
            ->loadMissing([
                'agreement.apartment.building',
                'agreement.buyer',
                'paymentAllocations.payment',
            ]);
    }
}
