<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SaleReservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordReservationDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('sale_reservation');

        return $reservation instanceof SaleReservation
            && ($this->user()?->can('recordDeposit', $reservation) ?? false);
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
