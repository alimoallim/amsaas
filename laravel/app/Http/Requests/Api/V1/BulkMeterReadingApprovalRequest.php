<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkMeterReadingApprovalRequest extends FormRequest
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
        $companyId = $this->user()->company_id;

        return [
            'reading_ids' => ['required', 'array', 'min:1', 'max:500'],
            'reading_ids.*' => [
                'required',
                'uuid',
                Rule::exists('meter_readings', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ];
    }
}
