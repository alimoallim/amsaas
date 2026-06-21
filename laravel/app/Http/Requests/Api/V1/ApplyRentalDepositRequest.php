<?php

namespace App\Http\Requests\Api\V1;

use App\Models\RentalAgreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyRentalDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', RentalAgreement::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'monthly_invoice_id' => [
                'required',
                'uuid',
                Rule::exists('monthly_invoices', 'id')->where('company_id', $companyId),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
