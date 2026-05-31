<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

        'name' => [

            'required',
            'string',
            'max:255',
        ],

        'code' => [

            'nullable',
            'string',
            'max:100',
        ],

        'type' => [

            'nullable',
            'string',
            'max:100',
        ],

        'city' => [

            'required',
            'string',
            'max:100',
        ],

        'country' => [

            'required',
            'string',
            'max:100',
        ],

        'timezone' => [

            'nullable',
            'string',
            'max:100',
        ],

        'operating_currency' => [

            'required',
            'string',
            'size:3',
        ],

        'address' => [

            'nullable',
            'string',
        ],

        'total_floors' => [

            'nullable',
            'integer',
            'min:1',
        ],

        'total_units' => [

            'nullable',
            'integer',
            'min:1',
        ],

        'description' => [

            'nullable',
            'string',
        ],

        'is_active' => [

            'boolean',
        ],
    ];
    }
}