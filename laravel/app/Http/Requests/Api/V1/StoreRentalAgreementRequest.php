<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Agreement;
use App\Models\Apartment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalAgreementRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return auth()->check();
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            'apartment_id' => [

                'required',

                'uuid',

                Rule::exists(
                    'apartments',
                    'id'
                )->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        auth()->user()->company_id
                    )
                ),
            ],

            'tenant_id' => [

                'required',

                'uuid',

                Rule::exists(
                    'tenants',
                    'id'
                )->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        auth()->user()->company_id
                    )
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Agreement Dates
            |--------------------------------------------------------------------------
            */

            'start_date' => [

                'required',

                'date',
            ],

            'end_date' => [

                'nullable',

                'date',

                'after:start_date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Financial Terms
            |--------------------------------------------------------------------------
            */

            'monthly_rent' => [

                'required',

                'numeric',

                'gt:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment Policy
            |--------------------------------------------------------------------------
            */

            'payment_due_day' => [

                'required',

                'integer',

                'between:1,28',
            ],

            /*
            |--------------------------------------------------------------------------
            | Optional Fields
            |--------------------------------------------------------------------------
            */

            'deposit_amount' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'notes' => [

                'nullable',

                'string',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Business Rule Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator(
        $validator
    ): void {

        $validator->after(

            function ($validator) {

                $apartment = Apartment::query()

                    ->where(
                        'id',
                        $this->apartment_id
                    )

                    ->where(
                        'company_id',
                        auth()->user()->company_id
                    )

                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Apartment Not Found
                |--------------------------------------------------------------------------
                */

                if (! $apartment) {

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Apartment Must Be Rental Enabled
                |--------------------------------------------------------------------------
                */

                if (

                    ! in_array(

                        $apartment->listing_type,

                        [

                            Apartment::LISTING_TYPE_RENTAL,

                            'hybrid',
                        ]
                    )
                ) {

                    $validator->errors()->add(

                        'apartment_id',

                        'Selected apartment is not rental-enabled.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Apartment Must Not Be Occupied
                |--------------------------------------------------------------------------
                */

                if (

                    $apartment->inventory_status
                    === Apartment::STATUS_OCCUPIED
                ) {

                    $validator->errors()->add(

                        'apartment_id',

                        'Apartment is already occupied.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent Multiple Active Agreements
                |--------------------------------------------------------------------------
                */

                $hasActiveAgreement = Agreement::query()

                    ->where(
                        'apartment_id',
                        $apartment->id
                    )

                    ->where(
                        'status',
                        Agreement::STATUS_ACTIVE
                    )

                    ->exists();

                if ($hasActiveAgreement) {

                    $validator->errors()->add(

                        'apartment_id',

                        'Apartment already has an active agreement.'
                    );
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'apartment_id.required' =>

                'Apartment selection is required.',

            'tenant_id.required' =>

                'Tenant selection is required.',

            'monthly_rent.gt' =>

                'Monthly rent must be greater than zero.',

            'payment_due_day.between' =>

                'Payment due day must be between 1 and 28.',

            'end_date.after' =>

                'End date must be after start date.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sanitization
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $this->merge([

            'monthly_rent' =>

                $this->monthly_rent !== null
                    ? trim($this->monthly_rent)
                    : null,

            'deposit_amount' =>

                $this->deposit_amount !== null
                    ? trim($this->deposit_amount)
                    : null,
        ]);
    }
}