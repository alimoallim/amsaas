<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'charge_type_id' => $this->charge_type_id,
            'charge_type' => $this->whenLoaded('chargeType', fn () => [
                'id' => $this->chargeType?->id,
                'code' => $this->chargeType?->code,
                'name' => $this->chargeType?->name,
                'ledger_account_code' => $this->chargeType?->ledger_account_code,
            ]),
            'line_type' => $this->line_type,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'amount' => $this->amount,
            'sort_order' => $this->sort_order,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
        ];
    }
}
