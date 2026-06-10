<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sale_price' => ['sometimes', 'numeric', 'min:0.01'],
            'down_payment' => ['sometimes', 'numeric', 'min:0'],
            'is_installment_sale' => ['sometimes', 'boolean'],
            'is_payment_plan' => ['sometimes', 'boolean'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'plan_duration_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'plan_duration_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'agreement_end_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'contract_date' => ['sometimes', 'nullable', 'date'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'signed_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'special_terms' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'broker_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'broker_commission' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
