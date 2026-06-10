<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'buyer_code' => $this->buyer_code,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'national_id' => $this->national_id,
            'nationality' => $this->nationality,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'address' => [
                'country' => $this->country,
                'city' => $this->city,
                'line' => $this->address,
                'postal_code' => $this->postal_code,
            ],
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'tenant_id' => $this->tenant_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'display_name' => $this->tenant->full_display_name ?? $this->tenant->display_name,
                'tenant_code' => $this->tenant->tenant_code,
            ]),
            'controls' => [
                'can_delete' => ! $this->payments_count
                    && ! $this->open_sale_agreements_count,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
