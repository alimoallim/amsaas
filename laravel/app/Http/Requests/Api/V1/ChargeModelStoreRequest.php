<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ChargeModel;
use App\Services\Billing\ChargeModelTierValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChargeModelStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'charge_type_id' => [
                'required',
                'uuid',
                'exists:charge_types,id',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('charge_models', 'code')
                    ->where(
                        fn($query) =>
                        $query->where(
                            'company_id',
                            auth()->user()->company_id
                        )
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'pricing_strategy' => [
                'required',
                Rule::in(
                    ChargeModel::STRATEGIES
                ),
            ],

            'billing_frequency' => [
                'required',
                Rule::in(
                    ChargeModel::BILLING_FREQUENCIES
                ),
            ],

            'meter_type' => [
                'nullable',
                Rule::in(
                    ChargeModel::METER_TYPES
                ),
            ],

            'base_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'minimum_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'maximum_amount' => [
                'nullable',
                'numeric',
                'gte:minimum_amount',
            ],

            'unit_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'percentage_rate' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'tier_configuration' => [
                'nullable',
                'array',
            ],

            'formula_expression' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'proration_enabled' => [
                'boolean',
            ],

            'grace_period_days' => [
                'nullable',
                'integer',
                'min:0',
                'max:365',
            ],

            'late_fee_enabled' => [
                'boolean',
            ],

            'late_fee_type' => [
                'nullable',
                Rule::in(
                    ChargeModel::LATE_FEE_TYPES
                ),
            ],

            'late_fee_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'taxable' => [
                'boolean',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'auto_generate' => [
                'boolean',
            ],

            'requires_approval' => [
                'boolean',
            ],

            'status' => [
                'required',
                Rule::in(
                    ChargeModel::STATUSES
                ),
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function withValidator(
        $validator
    ): void {

        $validator->after(

            function ($validator) {

                $strategy = $this->pricing_strategy;

                if ($strategy === ChargeModel::STRATEGY_FORMULA) {
                    $validator->errors()->add(
                        'pricing_strategy',
                        'Formula pricing is not available yet. Choose fixed, metered, tiered, or percentage.'
                    );
                }

                if (
                    $strategy ===
                    ChargeModel::STRATEGY_METERED
                ) {

                    if (blank($this->unit_rate)) {

                        $validator->errors()->add(
                            'unit_rate',
                            'Unit rate is required for metered pricing.'
                        );
                    }

                    if (blank($this->meter_type)) {

                        $validator->errors()->add(
                            'meter_type',
                            'Meter type is required for metered pricing.'
                        );
                    }
                }

                if (
                    $strategy ===
                    ChargeModel::STRATEGY_PERCENTAGE
                    &&
                    blank($this->percentage_rate)
                ) {

                    $validator->errors()->add(
                        'percentage_rate',
                        'Percentage rate is required.'
                    );
                }

                if ($strategy === ChargeModel::STRATEGY_TIERED) {
                    foreach (ChargeModelTierValidator::errors($this->tier_configuration) as $message) {
                        $validator->errors()->add('tier_configuration', $message);
                    }
                }
            }
        );
    }

    public function messages(): array
    {
        return [

            'charge_type_id.required' =>
            'Charge type is required.',

            'code.unique' =>
            'This charge model code already exists.',

            'effective_to.after_or_equal' =>
            'Effective end date must be after the start date.',
        ];
    }
}
