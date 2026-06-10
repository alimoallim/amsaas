<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SaleOwnershipApproval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveOwnershipTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'step' => ['required', 'string', Rule::in(SaleOwnershipApproval::STEPS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
