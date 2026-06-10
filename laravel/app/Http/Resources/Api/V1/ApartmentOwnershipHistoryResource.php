<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ApartmentOwnershipHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApartmentOwnershipHistory */
class ApartmentOwnershipHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_date' => $this->transfer_date?->format('Y-m-d'),
            'title_deed_number' => $this->title_deed_number,
            'notes' => $this->notes,
            'buyer' => $this->when(
                $this->relationLoaded('buyer') && $this->buyer,
                fn () => [
                    'id' => $this->buyer->id,
                    'full_name' => $this->buyer->full_name,
                    'buyer_code' => $this->buyer->buyer_code,
                ],
            ),
            'sale_agreement' => $this->when(
                $this->relationLoaded('saleAgreement') && $this->saleAgreement,
                fn () => [
                    'id' => $this->saleAgreement->id,
                    'agreement_number' => $this->saleAgreement->agreement?->agreement_number,
                ],
            ),
            'recorded_by' => $this->when(
                $this->relationLoaded('recordedBy') && $this->recordedBy,
                fn () => [
                    'id' => $this->recordedBy->id,
                    'name' => $this->recordedBy->name,
                ],
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
