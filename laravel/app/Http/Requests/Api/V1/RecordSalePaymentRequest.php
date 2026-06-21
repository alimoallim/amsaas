<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SaleAgreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SaleAgreement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'bank_transfer', 'mobile_money', 'cheque'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
