<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'apartment_id' => [
                'required',
                'uuid',
                Rule::exists('apartments', 'id')->where('company_id', $companyId),
            ],
            'buyer_id' => [
                'required',
                'uuid',
                Rule::exists('buyers', 'id')->where('company_id', $companyId),
            ],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'record_deposit' => ['sometimes', 'boolean'],
            'payment_date' => ['nullable', 'required_if:record_deposit,true', 'date'],
            'payment_method' => [
                'nullable',
                'required_if:record_deposit,true',
                'string',
                Rule::in(['cash', 'bank_transfer', 'mobile_money', 'cheque']),
            ],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
