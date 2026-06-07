<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Models\ChargeModel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesAgreementBilling
{
    protected function normalizeRecurringChargesInput(): void
    {
        $raw = $this->input('recurring_charges');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $this->merge([
                'recurring_charges' => is_array($decoded) ? $decoded : [],
            ]);
        }

        $rows = $this->input('recurring_charges', []);

        if (is_array($rows)) {
            $filtered = array_values(array_filter(
                $rows,
                fn ($row) => is_array($row) && filled($row['charge_model_id'] ?? null)
            ));

            $this->merge(['recurring_charges' => $filtered]);
        }

        if ($this->input('rent_charge_model_id') === '') {
            $this->merge(['rent_charge_model_id' => null]);
        }
    }

    protected function agreementBillingRules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'rent_charge_model_id' => [
                'nullable',
                'uuid',
                Rule::exists('charge_models', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT)
                ),
            ],
            'recurring_charges' => ['nullable', 'array'],
            'recurring_charges.*.id' => [
                'nullable',
                'uuid',
                Rule::exists('agreement_charges', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'recurring_charges.*.charge_model_id' => [
                'required',
                'uuid',
                Rule::exists('charge_models', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'recurring_charges.*.override_amount' => ['nullable', 'numeric', 'min:0'],
            'recurring_charges.*.override_unit_rate' => ['nullable', 'numeric', 'min:0'],
            'recurring_charges.*.custom_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function validateAgreementBillingRows(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rows = $this->input('recurring_charges', []);
            $companyId = auth()->user()->company_id;
            $seenChargeModels = [];

            foreach ($rows as $index => $row) {
                $modelId = $row['charge_model_id'] ?? null;
                if ($modelId) {
                    if (isset($seenChargeModels[$modelId])) {
                        $validator->errors()->add(
                            "recurring_charges.{$index}.charge_model_id",
                            'This charge model is already on the agreement — each model can only appear once.'
                        );
                    } else {
                        $seenChargeModels[$modelId] = true;
                    }
                }
                $modelId = $row['charge_model_id'] ?? null;
                if (! $modelId) {
                    continue;
                }

                $model = ChargeModel::query()
                    ->where('company_id', $companyId)
                    ->find($modelId);

                if (! $model) {
                    continue;
                }

                if (
                    $model->pricing_strategy === ChargeModel::STRATEGY_AGREEMENT_RENT
                ) {
                    $validator->errors()->add(
                        "recurring_charges.{$index}.charge_model_id",
                        'Rent is billed from monthly rent on this agreement — do not add it as a recurring line.'
                    );
                }

                if (
                    in_array(
                        $model->pricing_strategy,
                        [ChargeModel::STRATEGY_FLAT_FEE, ChargeModel::STRATEGY_FIXED],
                        true
                    )
                    && blank($row['override_amount'] ?? null)
                ) {
                    $validator->errors()->add(
                        "recurring_charges.{$index}.override_amount",
                        'Monthly amount is required for this service fee.'
                    );
                }
            }
        });
    }
}
