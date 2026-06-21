<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SaleAgreement;
use Illuminate\Foundation\Http\FormRequest;

class ApplySaleDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SaleAgreement::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
