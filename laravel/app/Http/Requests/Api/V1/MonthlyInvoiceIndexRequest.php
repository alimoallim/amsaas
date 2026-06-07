<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyInvoiceIndexRequest extends FormRequest
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
            'year' => 'nullable|integer|between:2020,2050',
            'month' => 'nullable|integer|between:1,12',
            'status' => 'nullable|string|in:draft,issued,finalized,partially_paid,paid,overdue,cancelled',
            'view' => 'nullable|string|in:attention,all',
            'building_id' => 'nullable|uuid|exists:buildings,id',
            'search' => 'nullable|string|max:120',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ];
    }
}
