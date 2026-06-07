<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgreementChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agreement_id' => $this->agreement_id,
            'charge_model_id' => $this->charge_model_id,
            'charge_type_id' => $this->charge_type_id,
            'custom_name' => $this->custom_name,
            'override_amount' => $this->override_amount,
            'override_unit_rate' => $this->override_unit_rate,
            'billing_start_date' => $this->billing_start_date?->toDateString(),
            'billing_end_date' => $this->billing_end_date?->toDateString(),
            'status' => $this->status,
            'priority' => $this->priority,
            'charge_model' => $this->whenLoaded(
                'chargeModel',
                fn () => [
                    'id' => $this->chargeModel->id,
                    'code' => $this->chargeModel->code,
                    'name' => $this->chargeModel->name,
                    'pricing_strategy' => $this->chargeModel->pricing_strategy,
                    'meter_type' => $this->chargeModel->meter_type,
                    'unit_rate' => $this->chargeModel->unit_rate,
                ]
            ),
            'charge_type' => $this->whenLoaded(
                'chargeType',
                fn () => [
                    'id' => $this->chargeType->id,
                    'code' => $this->chargeType->code,
                    'name' => $this->chargeType->name,
                    'category' => $this->chargeType->category,
                ]
            ),
        ];
    }
}
