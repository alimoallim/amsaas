<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Charge $charge */
        $charge = $this->resource;

        return [
            'id' => $charge->id,
            'charge_number' => $charge->charge_number,
            'reference_number' => $charge->reference_number,
            'company_id' => $charge->company_id,
            'category' => $charge->category,
            'billing_strategy' => $charge->billing_strategy,
            'status' => [
                'value' => $charge->status,
                'label' => str($charge->status)->replace('_', ' ')->title()->toString(),
            ],
            'currency' => $charge->currency,
            'description' => $charge->description,
            'notes' => $charge->notes,
            'amounts' => [
                'quantity' => (float) $charge->quantity,
                'unit_rate' => (float) $charge->unit_rate,
                'subtotal' => (float) $charge->subtotal_amount,
                'tax' => (float) $charge->tax_amount,
                'discount' => (float) $charge->discount_amount,
                'total' => (float) $charge->total_amount,
            ],
            'meter' => [
                'previous_reading' => $charge->meter_previous_reading !== null
                    ? (float) $charge->meter_previous_reading
                    : null,
                'current_reading' => $charge->meter_current_reading !== null
                    ? (float) $charge->meter_current_reading
                    : null,
                'consumption' => $charge->meter_consumption !== null
                    ? (float) $charge->meter_consumption
                    : null,
            ],
            'service_period' => [
                'start' => $charge->service_period_start?->toDateString(),
                'end' => $charge->service_period_end?->toDateString(),
            ],
            'snapshots' => [
                'building' => $charge->building_name_snapshot,
                'apartment' => $charge->apartment_label_snapshot,
                'tenant' => $charge->tenant_name_snapshot,
            ],
            'building_id' => $charge->building_id,
            'apartment_id' => $charge->apartment_id,
            'tenant_id' => $charge->tenant_id,
            'rental_agreement_id' => $charge->rental_agreement_id,
            'charge_type_id' => $charge->charge_type_id,
            'charge_model_id' => $charge->charge_model_id,
            'meter_reading_id' => $charge->meter_reading_id,
            'invoice_id' => $charge->invoice_id,
            'charge_type' => $this->whenLoaded('chargeType', fn () => [
                'id' => $charge->chargeType?->id,
                'code' => $charge->chargeType?->code,
                'name' => $charge->chargeType?->name,
            ]),
            'charge_model' => $this->whenLoaded('chargeModel', fn () => [
                'id' => $charge->chargeModel?->id,
                'code' => $charge->chargeModel?->code,
                'name' => $charge->chargeModel?->name,
                'pricing_strategy' => $charge->chargeModel?->pricing_strategy,
            ]),
            'meter_reading' => $this->whenLoaded('meterReading', fn () => [
                'id' => $charge->meterReading?->id,
                'reading_date' => $charge->meterReading?->reading_date?->toDateString(),
            ]),
            'timestamps' => [
                'charged_at' => $charge->charged_at?->toIso8601String(),
                'approved_at' => $charge->approved_at?->toIso8601String(),
                'invoiced_at' => $charge->invoiced_at?->toIso8601String(),
                'created_at' => $charge->created_at?->toIso8601String(),
            ],
            'controls' => [
                'can_approve' => $charge->status === Charge::STATUS_PENDING && ! $charge->invoice_id,
                'can_reject' => $charge->status === Charge::STATUS_PENDING && ! $charge->invoice_id,
                'can_view_reading' => (bool) $charge->meter_reading_id,
            ],
        ];
    }
}
