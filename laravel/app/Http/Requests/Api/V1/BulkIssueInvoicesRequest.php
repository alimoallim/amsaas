<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkIssueInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => 'required|integer|between:2020,2050',
            'month' => 'required|integer|between:1,12',
            'ids' => 'sometimes|array',
            'ids.*' => 'uuid|exists:monthly_invoices,id',
        ];
    }
}
