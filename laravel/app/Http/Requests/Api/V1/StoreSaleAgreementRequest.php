<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'sale_reservation_id' => [
                'nullable',
                'uuid',
                Rule::exists('sale_reservations', 'id')->where('company_id', $companyId),
            ],
            'apartment_id' => [
                'nullable',
                'required_without:sale_reservation_id',
                'uuid',
                Rule::exists('apartments', 'id')->where('company_id', $companyId),
            ],
            'buyer_id' => [
                'nullable',
                'required_without:sale_reservation_id',
                'uuid',
                Rule::exists('buyers', 'id')->where('company_id', $companyId),
            ],
            'sale_price' => ['required', 'numeric', 'min:0.01'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'is_installment_sale' => ['sometimes', 'boolean'],
            'is_payment_plan' => ['sometimes', 'boolean'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'plan_duration_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'plan_duration_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'agreement_end_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'contract_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'signed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'special_terms' => ['nullable', 'string', 'max:10000'],
            'broker_name' => ['nullable', 'string', 'max:255'],
            'broker_commission' => ['nullable', 'numeric', 'min:0'],
            'execute' => ['sometimes', 'boolean'],
        ];
    }
}
