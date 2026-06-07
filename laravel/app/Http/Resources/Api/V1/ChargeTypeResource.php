<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'description' => $this->description,
            'category' => $this->category,
            'billing_behavior' => $this->billing_behavior,
            'calculation_method' => $this->calculation_method,
            'billing_frequency' => $this->billing_frequency,
            'financial_classification' => $this->financial_classification,
            'default_currency' => $this->default_currency,
            'default_amount' => $this->default_amount,
            'default_percentage' => $this->default_percentage,
            'flags' => [
                'is_recurring' => $this->is_recurring,
                'is_metered' => $this->is_metered,
                'requires_meter_reading' => $this->requires_meter_reading,
                'is_taxable' => $this->is_taxable,
                'is_refundable' => $this->is_refundable,
                'auto_generate' => $this->auto_generate,
                'affects_occupancy' => $this->affects_occupancy,
            ],
            'permissions' => [
                'allow_manual_override' => $this->allow_manual_override,
                'allow_proration' => $this->allow_proration,
                'allow_discount' => $this->allow_discount,
                'allow_penalty' => $this->allow_penalty,
                'allow_adjustment' => $this->allow_adjustment,
            ],
            'ledger_account_code' => $this->ledger_account_code,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'audit' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
                'created_by' => $this->created_by,
                'updated_by' => $this->updated_by,
            ],
            
            // Dynamic UI capability metadata context block
            'controls' => [
                'can_edit' => $this->status !== 'archived',
                'can_delete' => $this->status === 'inactive' || $this->created_at?->diffInMinutes() < 60,
            ],
        ];
    }
}