<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SaleReservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_number' => $this->reservation_number,
            'status' => $this->status,
            'deposit_amount' => (float) $this->deposit_amount,
            'reserved_price' => $this->reserved_price !== null ? (float) $this->reserved_price : null,
            'currency' => $this->currency,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'deposit_paid_at' => $this->deposit_paid_at?->toIso8601String(),
            'expired_at' => $this->expired_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'notes' => $this->notes,
            'apartment' => $this->whenLoaded('apartment', fn () => [
                'id' => $this->apartment->id,
                'unit_number' => $this->apartment->unit_number,
                'inventory_status' => $this->apartment->inventory_status,
                'building' => $this->apartment->relationLoaded('building') && $this->apartment->building
                    ? ['id' => $this->apartment->building->id, 'name' => $this->apartment->building->name]
                    : null,
            ]),
            'buyer' => $this->whenLoaded('buyer', fn () => [
                'id' => $this->buyer->id,
                'buyer_code' => $this->buyer->buyer_code,
                'full_name' => $this->buyer->full_name,
                'email' => $this->buyer->email,
                'phone' => $this->buyer->phone,
            ]),
            'deposit_payment' => $this->whenLoaded('depositPayment', fn () => $this->depositPayment
                ? new PaymentResource($this->depositPayment)
                : null),
            'controls' => [
                'can_cancel' => in_array($this->status, SaleReservation::ACTIVE_STATUSES, true),
                'can_record_deposit' => $this->status === SaleReservation::STATUS_PENDING_DEPOSIT
                    && ! $this->deposit_payment_id,
                'can_create_contract' => $this->canConvertToContract(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
