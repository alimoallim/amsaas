<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized
     * to make this request.
     */
    public function authorize(): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation
        |--------------------------------------------------------------------------
        */

        return $this->tenant->company_id
            === $this->user()->company_id;
    }

    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([

            'email' =>

                $this->email
                    ? strtolower(trim($this->email))
                    : null,

            'display_name' =>

                $this->display_name
                    ? trim($this->display_name)
                    : null,
        ]);
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | ERP Identity
            |--------------------------------------------------------------------------
            */

            'tenant_type' => [

                'sometimes',

                'required',

                Rule::in([

                    'individual',
                    'company',
                    'government',
                    'ngo',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Human Identity
            |--------------------------------------------------------------------------
            */

            'first_name' => [

                'nullable',
                'string',
                'max:100',
            ],

            'middle_name' => [

                'nullable',
                'string',
                'max:100',
            ],

            'last_name' => [

                'nullable',
                'string',
                'max:100',
            ],

            'display_name' => [

                'sometimes',

                'required',

                'string',

                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Corporate Tenant
            |--------------------------------------------------------------------------
            */

            'company_name' => [

                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */

            'email' => [

                'nullable',

                'email',

                'max:255',

                Rule::unique(
                    'tenants',
                    'email'
                )
                ->ignore($this->tenant->id)
                ->where(function ($query) {

                    return $query->where(

                        'company_id',

                        auth()->user()->company_id
                    );
                }),
            ],

            'phone' => [

                'nullable',
                'string',
                'max:50',
            ],

            'alternate_phone' => [

                'nullable',
                'string',
                'max:50',
            ],

            /*
            |--------------------------------------------------------------------------
            | Legal / Government
            |--------------------------------------------------------------------------
            */

            'national_id' => [

                'nullable',
                'string',
                'max:100',
            ],

            'passport_number' => [

                'nullable',
                'string',
                'max:100',
            ],

            'tax_number' => [

                'nullable',
                'string',
                'max:100',
            ],

            'nationality' => [

                'nullable',
                'string',
                'max:100',
            ],

            'date_of_birth' => [

                'nullable',
                'date',
                'before:today',
            ],

            /*
            |--------------------------------------------------------------------------
            | Demographics
            |--------------------------------------------------------------------------
            */

            'gender' => [

                'nullable',

                Rule::in([

                    'male',
                    'female',
                    'other',
                ]),
            ],

            'occupation' => [

                'nullable',
                'string',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            'country' => [

                'nullable',
                'string',
                'max:100',
            ],

            'city' => [

                'nullable',
                'string',
                'max:100',
            ],

            'address' => [

                'nullable',
                'string',
            ],

            'postal_code' => [

                'nullable',
                'string',
                'max:50',
            ],

            /*
            |--------------------------------------------------------------------------
            | Emergency Contact
            |--------------------------------------------------------------------------
            */

            'emergency_contact_name' => [

                'nullable',
                'string',
                'max:255',
            ],

            'emergency_contact_phone' => [

                'nullable',
                'string',
                'max:50',
            ],

            'emergency_contact_relationship' => [

                'nullable',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Status
            |--------------------------------------------------------------------------
            */

            'status' => [

                'sometimes',

                'required',

                Rule::in([

                    'active',
                    'inactive',
                    'blacklisted',
                    'pending',
                    'archived',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | ERP Metadata
            |--------------------------------------------------------------------------
            */

            'notes' => [

                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'display_name.required' =>

                'Tenant display name is required.',

            'tenant_type.required' =>

                'Tenant type is required.',

            'status.required' =>

                'Tenant status is required.',
        ];
    }
}