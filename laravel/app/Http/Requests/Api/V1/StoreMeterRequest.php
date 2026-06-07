<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Apartment;
use App\Models\Meter;

use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized
     * to make this request.
     */
    public function authorize(): bool
{
    return $this->user() !== null;
}

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Property Hierarchy
            |--------------------------------------------------------------------------
            */

            'building_id' => [

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
                    ->when(
                        $this->filled('building_id'),
                        fn ($q) => $q->where('building_id', $this->building_id)
                    )
                ),
            ],

            'tenant_id' => [

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

                'required',

                'string',

                'max:100',

                Rule::unique(
                    'meters',
                    'meter_number'
                )
                ->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        $this->user()->company_id
                    )
                ),
            ],

            'serial_number' => [

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

                'required',

                Rule::in(
                    Meter::UTILITY_TYPES
                ),
            ],

            'ownership_type' => [

                'required',

                Rule::in(
                    Meter::OWNERSHIP_TYPES
                ),
            ],

            'meter_type' => [

                'required',

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

                'required',

                Rule::in(
                    Meter::MEASUREMENT_UNITS
                ),
            ],

            'initial_reading' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'current_reading' => [

                'nullable',

                'numeric',

                'min:0',

                'gte:initial_reading',
            ],

            'multiplier_factor' => [

                'nullable',

                'numeric',

                'min:0.0001',
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Details
            |--------------------------------------------------------------------------
            */

            'status' => [

                'nullable',

                Rule::in(
                    Meter::STATUSES
                ),
            ],

            'location_description' => [

                'nullable',

                'string',

                'max:255',
            ],

            'manufacturer' => [

                'nullable',

                'string',

                'max:150',
            ],

            'model_number' => [

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

                'nullable',

                'date',

                'before_or_equal:today',
            ],

            'inspection_due_date' => [

                'nullable',

                'date',

                'after_or_equal:today',
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Flags
            |--------------------------------------------------------------------------
            */

            'is_shared' => [

                'nullable',

                'boolean',
            ],

            'supports_remote_reading' => [

                'nullable',

                'boolean',
            ],

            'maintenance_required' => [

                'nullable',

                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [

                'nullable',

                'string',

                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            'metadata' => [

                'nullable',

                'array',
            ],
        ];
    }

    /**
     * Custom Validation Messages
     */
    public function messages(): array
    {
        return [

            'meter_number.unique' =>

                'Meter number already exists within your company.',

            'current_reading.gte' =>

                'Current reading must be greater than or equal to the initial reading.',

            'installation_date.before_or_equal' =>

                'Installation date cannot be in the future.',

            'inspection_due_date.after_or_equal' =>

                'Inspection due date must be today or a future date.',
        ];
    }

    /**
     * Prepare Input Before Validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([

            'meter_number' =>

                strtoupper(
                    trim(
                        (string)
                        $this->meter_number
                    )
                ),

            'serial_number' =>

                $this->serial_number
                    ? strtoupper(
                        trim(
                            $this->serial_number
                        )
                    )
                    : null,

            'multiplier_factor' =>

                $this->multiplier_factor
                ?? 1,

            'status' =>

                $this->status
                ?? Meter::STATUS_ACTIVE,
        ]);
    }

    /**
     * Additional Business Validation
     */
    public function withValidator(
        $validator
    ): void {

        $validator->after(

            function ($validator) {

                /*
                |--------------------------------------------------------------------------
                | Unit assignment requires a building
                |--------------------------------------------------------------------------
                */

                if ($this->filled('apartment_id') && ! $this->building_id) {

                    $validator->errors()->add(

                        'building_id',

                        'Select a building when assigning a unit.'
                    );
                }

                if ($this->filled('apartment_id') && $this->filled('building_id')) {

                    $belongs = Apartment::query()
                        ->where('id', $this->apartment_id)
                        ->where('building_id', $this->building_id)
                        ->where('company_id', $this->user()->company_id)
                        ->exists();

                    if (! $belongs) {

                        $validator->errors()->add(

                            'apartment_id',

                            'The selected unit does not belong to this building.'
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Building ownership
                |--------------------------------------------------------------------------
                */

                if ($this->ownership_type === Meter::OWNERSHIP_BUILDING) {

                    if (! $this->building_id) {

                        $validator->errors()->add(
                            'building_id',
                            'Select the building this meter serves.'
                        );
                    }

                    if ($this->filled('apartment_id')) {

                        $validator->errors()->add(
                            'apartment_id',
                            'Building-level meters must not be assigned to a unit.'
                        );
                    }

                    if ($this->filled('tenant_id')) {

                        $validator->errors()->add(
                            'tenant_id',
                            'Building-level meters must not be assigned to a tenant.'
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Shared / apartment ownership — building required
                |--------------------------------------------------------------------------
                */

                if (
                    in_array($this->ownership_type, [Meter::OWNERSHIP_SHARED, Meter::OWNERSHIP_APARTMENT], true)
                    && ! $this->building_id
                ) {

                    $validator->errors()->add(
                        'building_id',
                        'Select a building for this meter.'
                    );
                }

                if (
                    $this->boolean('is_shared')
                    && ! $this->building_id
                ) {

                    $validator->errors()->add(
                        'building_id',
                        'Shared meters must belong to a building.'
                    );
                }

                if (
                    $this->ownership_type === Meter::OWNERSHIP_APARTMENT
                    && ! $this->apartment_id
                ) {

                    $validator->errors()->add(
                        'apartment_id',
                        'Select the unit this meter serves.'
                    );
                }

                if (
                    $this->ownership_type === Meter::OWNERSHIP_TENANT
                    && ! $this->tenant_id
                ) {

                    $validator->errors()->add(
                        'tenant_id',
                        'Select the tenant billed for this meter.'
                    );
                }

                if (
                    $this->ownership_type === Meter::OWNERSHIP_TENANT
                    && $this->filled('apartment_id')
                ) {

                    $validator->errors()->add(
                        'apartment_id',
                        'Tenant ownership uses the tenant field only — do not assign a unit.'
                    );
                }
            }
        );
    }
}