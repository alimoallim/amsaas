<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment
            && ($this->user()?->can('refund', $payment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'allocation_ids' => ['nullable', 'array'],
            'allocation_ids.*' => ['uuid'],
        ];
    }
}
