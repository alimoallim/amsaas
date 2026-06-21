<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SaleAgreement;
use App\Models\SaleOwnershipApproval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveOwnershipTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SaleAgreement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'step' => ['required', 'string', Rule::in(SaleOwnershipApproval::STEPS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
