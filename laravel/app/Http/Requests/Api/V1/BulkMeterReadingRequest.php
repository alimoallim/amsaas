<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkMeterReadingRequest extends FormRequest
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
            'reading_date' => ['required', 'date'],
            'readings' => ['required', 'array', 'min:1', 'max:500'],
            'readings.*.meter_id' => ['required', 'uuid', 'exists:meters,id'],
            'readings.*.current_reading' => ['nullable', 'numeric', 'min:0'],
            'readings.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
