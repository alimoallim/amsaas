<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Agreement;
use App\Models\Apartment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRentalAgreementRequest extends FormRequest
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
            | Core Agreement Relationships
            |--------------------------------------------------------------------------
            |
            | These become immutable once agreement is active.
            |--------------------------------------------------------------------------
            */

            'apartment_id' => [

                'sometimes',

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

                'sometimes',

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

                'sometimes',

                'date',
            ],

            'end_date' => [

                'nullable',

                'date',

                'after:start_date',
            ],

            'signed_at' => [

                'nullable',

                'date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Financial Terms
            |--------------------------------------------------------------------------
            */

            'monthly_rent' => [

                'sometimes',

                'numeric',

                'gt:0',
            ],

            'deposit_amount' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'contract_amount' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'currency' => [

                'nullable',

                'string',

                'max:10',
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment Configuration
            |--------------------------------------------------------------------------
            */

            'payment_due_day' => [

                'sometimes',

                'integer',

                'between:1,28',
            ],

            /*
            |--------------------------------------------------------------------------
            | Utilities
            |--------------------------------------------------------------------------
            */

            'includes_water' => [

                'nullable',

                'boolean',
            ],

            'includes_electricity' => [

                'nullable',

                'boolean',
            ],

            'includes_internet' => [

                'nullable',

                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Renewal Policy
            |--------------------------------------------------------------------------
            */

            'auto_renew' => [

                'nullable',

                'boolean',
            ],

            'renewal_notice_days' => [

                'nullable',

                'integer',

                'min:1',

                'max:365',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status Security
            |--------------------------------------------------------------------------
            |
            | Prevent direct workflow manipulation.
            |--------------------------------------------------------------------------
            */

            'status' => [

                'prohibited',
            ],

            /*
            |--------------------------------------------------------------------------
            | Audit Security
            |--------------------------------------------------------------------------
            */

            'company_id' => [

                'prohibited',
            ],

            'agreement_number' => [

                'prohibited',
            ],

            'agreement_type' => [

                'prohibited',
            ],

            'approved_by' => [

                'prohibited',
            ],

            'approved_at' => [

                'prohibited',
            ],

            'terminated_by' => [

                'prohibited',
            ],

            'terminated_at' => [

                'prohibited',
            ],

            'created_by' => [

                'prohibited',
            ],

            'updated_by' => [

                'prohibited',
            ],

            /*
            |--------------------------------------------------------------------------
            | Files
            |--------------------------------------------------------------------------
            */

            'contract_file' => [

                'nullable',

                'file',

                'mimes:pdf',

                'max:10240',
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [

                'nullable',

                'string',
            ],

            'special_terms' => [

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

                $agreement = $this->route(
                    'rentalAgreement'
                );

                if (! $agreement) {

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Active Agreements Restrictions
                |--------------------------------------------------------------------------
                */

                if (

                    $agreement
                        ->agreement
                        ->status

                    === Agreement::STATUS_ACTIVE
                ) {

                    $restrictedFields = [

                        'apartment_id',

                        'tenant_id',

                        'start_date',
                    ];

                    foreach (

                        $restrictedFields
                        as $field

                    ) {

                        if (

                            $this->has($field)
                        ) {

                            $validator->errors()->add(

                                $field,

                                'This field cannot be modified after agreement activation.'
                            );
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Terminated Agreements Are Read-Only
                |--------------------------------------------------------------------------
                */

                if (

                    in_array(

                        $agreement
                            ->agreement
                            ->status,

                        [

                            Agreement::STATUS_TERMINATED,

                            Agreement::STATUS_COMPLETED,

                            Agreement::STATUS_CANCELLED,
                        ]
                    )
                ) {

                    $validator->errors()->add(

                        'agreement',

                        'This agreement can no longer be modified.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Apartment Validation
                |--------------------------------------------------------------------------
                */

                if (

                    $this->filled(
                        'apartment_id'
                    )

                ) {

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

                                Apartment::TYPE_RENTAL,

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
                    | Apartment Cannot Be Sold
                    |--------------------------------------------------------------------------
                    */

                    if (

                        $apartment->inventory_status
                        === Apartment::STATUS_SOLD
                    ) {

                        $validator->errors()->add(

                            'apartment_id',

                            'Sold apartments cannot have rental agreements.'
                        );
                    }
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

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

            'currency' =>

                $this->currency
                    ? strtoupper($this->currency)
                    : null,

            'auto_renew' =>

                filter_var(

                    $this->auto_renew,

                    FILTER_VALIDATE_BOOLEAN
                ),

            'includes_water' =>

                filter_var(

                    $this->includes_water,

                    FILTER_VALIDATE_BOOLEAN
                ),

            'includes_electricity' =>

                filter_var(

                    $this->includes_electricity,

                    FILTER_VALIDATE_BOOLEAN
                ),

            'includes_internet' =>

                filter_var(

                    $this->includes_internet,

                    FILTER_VALIDATE_BOOLEAN
                ),
        ]);
    }
}