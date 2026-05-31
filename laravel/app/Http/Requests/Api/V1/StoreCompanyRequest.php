<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'email' => [
                'nullable',
                'email',
                'max:255'
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50'
            ],

            'website' => [
                'nullable',
                'url',
                'max:255'
            ],

            'address' => [
                'nullable',
                'string'
            ],

            'city' => [
                'nullable',
                'string',
                'max:100'
            ],

            'country' => [
                'nullable',
                'string',
                'max:100'
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:100'
            ],

            'tax_number' => [
                'nullable',
                'string',
                'max:100'
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:2048'
            ],

            'is_active' => [
                'boolean'
            ],
        ];
    }
}