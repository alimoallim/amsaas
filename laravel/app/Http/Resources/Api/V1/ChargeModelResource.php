<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ChargeModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeModelResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'company_id' =>
                $this->company_id,

            'charge_type_id' =>
                $this->charge_type_id ?? $this->chargeType?->id,

            'code' =>
                $this->code,

            'name' =>
                $this->name,

            'description' =>
                $this->description,

            'currency' =>
                $this->currency,

            'base_amount' =>
                $this->base_amount,

            'minimum_amount' =>
                $this->minimum_amount,

            'maximum_amount' =>
                $this->maximum_amount,

            'unit_rate' =>
                $this->unit_rate,

            'percentage_rate' =>
                $this->percentage_rate,

            'billing_frequency' =>
                $this->billing_frequency,

            'pricing_strategy' =>
                $this->pricing_strategy,

            'meter_type' =>
                $this->meter_type,

            'utility_type' =>
                $this->meter_type,

            'tier_configuration' =>
                $this->tier_configuration,

            'formula_expression' =>
                $this->formula_expression,

            'proration_enabled' =>
                $this->proration_enabled,

            'grace_period_days' =>
                $this->grace_period_days,

            'late_fee_enabled' =>
                $this->late_fee_enabled,

            'late_fee_type' =>
                $this->late_fee_type,

            'late_fee_value' =>
                $this->late_fee_value,

            'taxable' =>
                $this->taxable,

            'tax_rate' =>
                $this->tax_rate,

            'effective_from' =>
                optional(
                    $this->effective_from
                )?->toDateString(),

            'effective_to' =>
                optional(
                    $this->effective_to
                )?->toDateString(),

            'auto_generate' =>
                $this->auto_generate,

            'requires_approval' =>
                $this->requires_approval,

            'status' =>
                $this->status,

            'sort_order' =>
                $this->sort_order,

            'metadata' =>
                $this->metadata,

            'is_active' =>
                $this->isActive(),

            'is_currently_effective' =>
                $this->isCurrentlyEffective(),

            'controls' => [
                'can_edit' => $this->status !== ChargeModel::STATUS_ARCHIVED,
                'can_delete' => in_array($this->status, [ChargeModel::STATUS_DRAFT, ChargeModel::STATUS_INACTIVE], true),
                'can_activate' => $this->status === ChargeModel::STATUS_INACTIVE,
                'can_clone' => true,
                'formula_supported' => $this->pricing_strategy !== ChargeModel::STRATEGY_FORMULA,
            ],

            'charge_type' =>
                $this->whenLoaded(
                    'chargeType',
                    fn () => [

                        'id' =>
                            $this->chargeType->id,

                        'name' =>
                            $this->chargeType->name,

                        'code' =>
                            $this->chargeType->code,
                    ]
                ),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}