<?php

namespace App\Services\Sales;

use App\Exceptions\BusinessRuleException;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Payment;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use App\Services\PaymentService;
use App\Services\Property\ApartmentInventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        private readonly ApartmentInventoryService $inventory,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @param  array{
     *   apartment_id: string,
     *   buyer_id: string,
     *   deposit_amount: float|string,
     *   expiry_date?: string,
     *   notes?: string,
     *   record_deposit?: bool,
     *   payment_date?: string,
     *   payment_method?: string,
     *   reference_number?: string,
     *   payment_notes?: string,
     * }  $data
     */
    public function create(User $actor, array $data): SaleReservation
    {
        return DB::transaction(function () use ($actor, $data) {
            $apartment = Apartment::query()
                ->where('id', $data['apartment_id'])
                ->where('company_id', $actor->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $buyer = Buyer::query()
                ->where('id', $data['buyer_id'])
                ->where('company_id', $actor->company_id)
                ->firstOrFail();

            $this->assertNoActiveReservation($apartment->id, $actor->company_id);
            $this->inventory->assertCanReserveForSale($apartment);

            $depositAmount = round((float) $data['deposit_amount'], 2);
            if ($depositAmount < 0) {
                throw ValidationException::withMessages([
                    'deposit_amount' => ['Deposit amount cannot be negative.'],
                ]);
            }

            $expiryDate = isset($data['expiry_date'])
                ? Carbon::parse($data['expiry_date'])->startOfDay()
                : now()->addDays(7)->startOfDay();

            $reservation = SaleReservation::create([
                'company_id' => $actor->company_id,
                'reservation_number' => $this->generateReservationNumber(),
                'apartment_id' => $apartment->id,
                'buyer_id' => $buyer->id,
                'deposit_amount' => $depositAmount,
                'reserved_price' => $apartment->market_sale_price,
                'currency' => strtoupper($apartment->currency ?? 'USD'),
                'expiry_date' => $expiryDate->toDateString(),
                'status' => SaleReservation::STATUS_PENDING_DEPOSIT,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->inventory->markReservedForSale(
                $apartment,
                "Sale reservation {$reservation->reservation_number}",
            );

            if (! empty($data['record_deposit']) && $depositAmount > 0) {
                $this->attachDepositPayment($actor, $reservation, $buyer, [
                    'amount' => $depositAmount,
                    'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['payment_notes'] ?? "Deposit for {$reservation->reservation_number}",
                ]);
            }

            return $reservation->fresh(['apartment.building', 'buyer', 'depositPayment']);
        });
    }

    /**
     * @param  array{amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $paymentData
     */
    public function recordDeposit(User $actor, SaleReservation $reservation, array $paymentData): SaleReservation
    {
        return DB::transaction(function () use ($actor, $reservation, $paymentData) {
            $this->assertCompanyOwns($actor, $reservation);

            if ($reservation->status !== SaleReservation::STATUS_PENDING_DEPOSIT) {
                throw new BusinessRuleException(
                    'Only pending reservations can receive a deposit.',
                    'RESERVATION_NOT_PENDING',
                );
            }

            if ($reservation->deposit_payment_id) {
                throw new BusinessRuleException(
                    'Deposit has already been recorded for this reservation.',
                    'RESERVATION_DEPOSIT_PAID',
                );
            }

            $buyer = Buyer::query()
                ->where('id', $reservation->buyer_id)
                ->where('company_id', $actor->company_id)
                ->firstOrFail();

            $amount = round((float) $paymentData['amount'], 2);
            if ($amount < (float) $reservation->deposit_amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Deposit payment must be at least the required deposit amount.'],
                ]);
            }

            $this->attachDepositPayment($actor, $reservation, $buyer, $paymentData);

            return $reservation->fresh(['apartment.building', 'buyer', 'depositPayment']);
        });
    }

    public function cancel(User $actor, SaleReservation $reservation, ?string $reason = null): SaleReservation
    {
        return DB::transaction(function () use ($actor, $reservation, $reason) {
            $this->assertCompanyOwns($actor, $reservation);

            if (! $reservation->isActive()) {
                throw new BusinessRuleException(
                    'Only active reservations can be cancelled.',
                    'RESERVATION_NOT_ACTIVE',
                );
            }

            $reservation->update([
                'status' => SaleReservation::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes' => trim(($reservation->notes ?? '')."\nCancelled: ".($reason ?? 'Manual cancellation')),
            ]);

            $this->releaseApartmentIfHeldByReservation($reservation, 'Reservation cancelled');

            return $reservation->fresh(['apartment.building', 'buyer', 'depositPayment']);
        });
    }

    /**
     * @return array{expired: int, released: int}
     */
    public function expireDueReservations(Carbon $asOf, ?string $companyId = null): array
    {
        $stats = ['expired' => 0, 'released' => 0];

        $query = SaleReservation::query()
            ->where('status', SaleReservation::STATUS_PENDING_DEPOSIT)
            ->whereNull('deposit_payment_id')
            ->whereDate('expiry_date', '<', $asOf->toDateString());

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->orderBy('expiry_date')->chunkById(50, function ($reservations) use (&$stats) {
            foreach ($reservations as $reservation) {
                DB::transaction(function () use ($reservation, &$stats) {
                    $locked = SaleReservation::query()
                        ->where('id', $reservation->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $locked || $locked->status !== SaleReservation::STATUS_PENDING_DEPOSIT) {
                        return;
                    }

                    $locked->update([
                        'status' => SaleReservation::STATUS_EXPIRED,
                        'expired_at' => now(),
                    ]);

                    $this->releaseApartmentIfHeldByReservation($locked, 'Reservation expired without deposit');
                    $stats['expired']++;
                    $stats['released']++;
                });
            }
        });

        return $stats;
    }

    /**
     * @param  array{amount: float|string, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $paymentData
     */
    private function attachDepositPayment(
        User $actor,
        SaleReservation $reservation,
        Buyer $buyer,
        array $paymentData,
    ): void {
        $payment = $this->payments->recordBuyerPayment($actor, [
            'buyer_id' => $buyer->id,
            'amount' => $paymentData['amount'],
            'payment_date' => $paymentData['payment_date'],
            'payment_method' => $paymentData['payment_method'],
            'reference_number' => $paymentData['reference_number'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
        ]);

        app(JournalEntryService::class)->postCustomerDeposit($payment, $actor->id);

        $reservation->update([
            'status' => SaleReservation::STATUS_CONFIRMED,
            'deposit_payment_id' => $payment->id,
            'deposit_paid_at' => now(),
        ]);
    }

    private function releaseApartmentIfHeldByReservation(SaleReservation $reservation, string $reason): void
    {
        $apartment = Apartment::query()
            ->where('id', $reservation->apartment_id)
            ->lockForUpdate()
            ->first();

        if (! $apartment) {
            return;
        }

        $hasOtherActive = SaleReservation::query()
            ->where('apartment_id', $apartment->id)
            ->where('id', '!=', $reservation->id)
            ->whereIn('status', SaleReservation::ACTIVE_STATUSES)
            ->exists();

        if ($hasOtherActive) {
            return;
        }

        if ($apartment->inventory_status === Apartment::STATUS_RESERVED) {
            $this->inventory->release($apartment, $reason);
        }
    }

    private function assertNoActiveReservation(string $apartmentId, string $companyId): void
    {
        $exists = SaleReservation::query()
            ->where('company_id', $companyId)
            ->where('apartment_id', $apartmentId)
            ->whereIn('status', SaleReservation::ACTIVE_STATUSES)
            ->exists();

        if ($exists) {
            throw new BusinessRuleException(
                'This unit already has an active sale reservation.',
                'APARTMENT_ALREADY_RESERVED',
            );
        }
    }

    private function assertCompanyOwns(User $actor, SaleReservation $reservation): void
    {
        if ($reservation->company_id !== $actor->company_id) {
            throw new BusinessRuleException('Unauthorized reservation access.', 'RESERVATION_FORBIDDEN');
        }
    }

    private function generateReservationNumber(): string
    {
        return 'RES-'.now()->format('Ym').'-'.strtoupper(Str::random(6));
    }
}
