<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlyInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'apartment_id' => [
                'required',
                'uuid',
                Rule::exists('apartments', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'billing_year' => ['required', 'integer', 'between:2020,2050'],
            'billing_month' => ['required', 'integer', 'between:1,12'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'line_items' => ['sometimes', 'array', 'min:1'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:500'],
            'line_items.*.line_type' => [
                'required_with:line_items',
                Rule::in(['rent', 'utility', 'electricity', 'water', 'gas', 'service', 'installment', 'other']),
            ],
            'line_items.*.quantity' => ['required_with:line_items', 'numeric', 'min:0.001'],
            'line_items.*.unit_price' => ['required_with:line_items', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'line_items.min' => 'Add at least one line item.',
            'line_items.*.unit_price.min' => 'Each line must have a positive unit price.',
            'due_date.after_or_equal' => 'Due date must be on or after the issue date.',
        ];
    }
}
