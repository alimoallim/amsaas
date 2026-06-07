<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ChargeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChargeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $chargeType = $this->route('charge_type');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('charge_types')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($chargeType),
            ],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in(ChargeType::CATEGORIES)],
            'billing_behavior' => ['required', 'string', Rule::in(ChargeType::BILLING_BEHAVIORS)],
            'calculation_method' => ['required', 'string', Rule::in(ChargeType::CALCULATION_METHODS)],
            'billing_frequency' => ['required', 'string', Rule::in(ChargeType::BILLING_FREQUENCIES)],
            'financial_classification' => ['required', 'string', Rule::in(ChargeType::FINANCIAL_CLASSIFICATIONS)],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.9999'],
            'default_percentage' => ['nullable', 'numeric', 'min:0', 'max:100.0000'],
            'is_recurring' => ['required', 'boolean'],
            'is_metered' => ['required', 'boolean'],
            'requires_meter_reading' => ['required', 'boolean'],
            'is_taxable' => ['required', 'boolean'],
            'is_refundable' => ['required', 'boolean'],
            'allow_manual_override' => ['required', 'boolean'],
            'allow_proration' => ['required', 'boolean'],
            'allow_discount' => ['required', 'boolean'],
            'allow_penalty' => ['required', 'boolean'],
            'allow_adjustment' => ['required', 'boolean'],
            'auto_generate' => ['required', 'boolean'],
            'affects_occupancy' => ['required', 'boolean'],
            'ledger_account_code' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(ChargeType::STATUSES)],
        ];
    }
}