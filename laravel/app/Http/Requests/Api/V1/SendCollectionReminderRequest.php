<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SendCollectionReminderRequest extends FormRequest
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
            'flag_ids' => 'required|array|min:1|max:100',
            'flag_ids.*' => 'uuid|exists:delinquency_flags,id',
        ];
    }
}
