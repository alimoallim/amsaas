<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SaleAgreement;
use Illuminate\Foundation\Http\FormRequest;

class IssueTitleDeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SaleAgreement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title_deed_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
