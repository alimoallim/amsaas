<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Meter;

use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeterRequest extends FormRequest
{
    /**
     * Authorize Request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        /** @var Meter $meter */
        $meter = $this->route('meter');

        return [

            /*
            |--------------------------------------------------------------------------
            | Property Hierarchy
            |--------------------------------------------------------------------------
            */

            'building_id' => [

                'sometimes',

                'nullable',

                'uuid',

                Rule::exists(
                    'buildings',
                    'id'
                )
                ->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        $this->user()->company_id
                    )
                ),
            ],

            'apartment_id' => [

                'sometimes',

                'nullable',

                'uuid',

                Rule::exists(
                    'apartments',
                    'id'
                )
                ->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        $this->user()->company_id
                    )
                ),
            ],

            'tenant_id' => [

                'sometimes',

                'nullable',

                'uuid',

                Rule::exists(
                    'tenants',
                    'id'
                )
                ->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        $this->user()->company_id
                    )
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Meter Identity
            |--------------------------------------------------------------------------
            */

            'meter_number' => [

                'sometimes',

                'string',

                'max:100',

                Rule::unique(
                    'meters',
                    'meter_number'
                )
                ->ignore($meter?->id)

                ->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        $this->user()->company_id
                    )
                ),
            ],

            'serial_number' => [

                'sometimes',

                'nullable',

                'string',

                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Utility Classification
            |--------------------------------------------------------------------------
            */

            'utility_type' => [

                'sometimes',

                Rule::in(
                    Meter::UTILITY_TYPES
                ),
            ],

            'ownership_type' => [

                'sometimes',

                Rule::in(
                    Meter::OWNERSHIP_TYPES
                ),
            ],

            'meter_type' => [

                'sometimes',

                Rule::in(
                    Meter::METER_TYPES
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Measurement
            |--------------------------------------------------------------------------
            */

            'measurement_unit' => [

                'sometimes',

                Rule::in(
                    Meter::MEASUREMENT_UNITS
                ),
            ],

            'initial_reading' => [

                'sometimes',

                'numeric',

                'min:0',
            ],

            'current_reading' => [

                'sometimes',

                'numeric',

                'min:0',
            ],

            'multiplier_factor' => [

                'sometimes',

                'numeric',

                'min:0.0001',
            ],

            /*
            |--------------------------------------------------------------------------
            | Lifecycle Status
            |--------------------------------------------------------------------------
            */

            'status' => [

                'sometimes',

                Rule::in(
                    Meter::STATUSES
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Details
            |--------------------------------------------------------------------------
            */

            'location_description' => [

                'sometimes',

                'nullable',

                'string',

                'max:255',
            ],

            'manufacturer' => [

                'sometimes',

                'nullable',

                'string',

                'max:150',
            ],

            'model_number' => [

                'sometimes',

                'nullable',

                'string',

                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Lifecycle Dates
            |--------------------------------------------------------------------------
            */

            'installation_date' => [

                'sometimes',

                'nullable',

                'date',
            ],

            'inspection_due_date' => [

                'sometimes',

                'nullable',

                'date',
            ],

            'decommissioned_at' => [

                'sometimes',

                'nullable',

                'date',
            ],

            'last_maintenance_at' => [

                'sometimes',

                'nullable',

                'date',
            ],

            'last_inspected_at' => [

                'sometimes',

                'nullable',

                'date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Flags
            |--------------------------------------------------------------------------
            */

            'is_shared' => [

                'sometimes',

                'boolean',
            ],

            'supports_remote_reading' => [

                'sometimes',

                'boolean',
            ],

            'maintenance_required' => [

                'sometimes',

                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [

                'sometimes',

                'nullable',

                'string',

                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Flexible Metadata
            |--------------------------------------------------------------------------
            */

            'metadata' => [

                'sometimes',

                'nullable',

                'array',
            ],
        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'meter_number.unique' =>

                'Meter number already exists within your company.',

            'current_reading.min' =>

                'Current reading cannot be negative.',

            'multiplier_factor.min' =>

                'Multiplier factor must be greater than zero.',
        ];
    }

    /**
     * Prepare Data Before Validation
     */
    protected function prepareForValidation(): void
    {
        if (
            $this->has(
                'meter_number'
            )
        ) {

            $this->merge([

                'meter_number' =>

                    strtoupper(
                        trim(
                            (string)
                            $this->meter_number
                        )
                    ),
            ]);
        }

        if (
            $this->has(
                'serial_number'
            )
        ) {

            $this->merge([

                'serial_number' =>

                    $this->serial_number
                        ? strtoupper(
                            trim(
                                $this->serial_number
                            )
                        )
                        : null,
            ]);
        }
    }

    /**
     * Additional Business Validation
     */
    public function withValidator(
        $validator
    ): void {

        $validator->after(

            function ($validator) {

                /** @var Meter $meter */
                $meter =
                    $this->route(
                        'meter'
                    );

                /*
                |--------------------------------------------------------------------------
                | Prevent Invalid Lifecycle Transition
                |--------------------------------------------------------------------------
                */

                if (

                    $meter?->isDecommissioned()

                    &&

                    $this->status ===
                    Meter::STATUS_ACTIVE
                ) {

                    $validator->errors()->add(

                        'status',

                        'Decommissioned meters cannot be directly reactivated.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Shared Meter Validation
                |--------------------------------------------------------------------------
                */

                if (

                    $this->boolean(
                        'is_shared'
                    )

                    &&

                    !$this->building_id

                    &&

                    !$meter?->building_id
                ) {

                    $validator->errors()->add(

                        'building_id',

                        'Shared meters must belong to a building.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Apartment Ownership Validation
                |--------------------------------------------------------------------------
                */

                if (

                    $this->ownership_type ===
                    Meter::OWNERSHIP_APARTMENT

                    &&

                    !$this->apartment_id

                    &&

                    !$meter?->apartment_id
                ) {

                    $validator->errors()->add(

                        'apartment_id',

                        'Apartment ownership meters require apartment assignment.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Tenant Ownership Validation
                |--------------------------------------------------------------------------
                */

                if (

                    $this->ownership_type ===
                    Meter::OWNERSHIP_TENANT

                    &&

                    !$this->tenant_id

                    &&

                    !$meter?->tenant_id
                ) {

                    $validator->errors()->add(

                        'tenant_id',

                        'Tenant ownership meters require tenant assignment.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Reading Integrity Validation
                |--------------------------------------------------------------------------
                */

                if (

                    $this->filled(
                        'current_reading'
                    )

                    &&

                    $meter

                    &&

                    $this->current_reading <
                    $meter->current_reading
                ) {

                    $validator->errors()->add(

                        'current_reading',

                        'Current reading cannot be lower than the existing reading.'
                    );
                }
            }
        );
    }
}