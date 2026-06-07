<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Apartment;
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
                    ->when(
                        $this->filled('building_id'),
                        fn ($q) => $q->where('building_id', $this->building_id)
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

                $buildingId = $this->has('building_id')
                    ? $this->building_id
                    : $meter?->building_id;

                $apartmentId = $this->has('apartment_id')
                    ? $this->apartment_id
                    : $meter?->apartment_id;

                /*
                |--------------------------------------------------------------------------
                | Unit assignment requires a building
                |--------------------------------------------------------------------------
                */

                if ($apartmentId && ! $buildingId) {

                    $validator->errors()->add(

                        'building_id',

                        'Select a building when assigning a unit.'
                    );
                }

                if ($apartmentId && $buildingId) {

                    $belongs = Apartment::query()
                        ->where('id', $apartmentId)
                        ->where('building_id', $buildingId)
                        ->where('company_id', $this->user()->company_id)
                        ->exists();

                    if (! $belongs) {

                        $validator->errors()->add(

                            'apartment_id',

                            'The selected unit does not belong to this building.'
                        );
                    }
                }

                $ownershipType = $this->ownership_type ?? $meter?->ownership_type;

                $tenantId = $this->has('tenant_id')
                    ? $this->tenant_id
                    : $meter?->tenant_id;

                if ($ownershipType === Meter::OWNERSHIP_BUILDING) {

                    if (! $buildingId) {

                        $validator->errors()->add(
                            'building_id',
                            'Select the building this meter serves.'
                        );
                    }

                    if ($apartmentId) {

                        $validator->errors()->add(
                            'apartment_id',
                            'Building-level meters must not be assigned to a unit.'
                        );
                    }

                    if ($tenantId) {

                        $validator->errors()->add(
                            'tenant_id',
                            'Building-level meters must not be assigned to a tenant.'
                        );
                    }
                }

                if (
                    in_array($ownershipType, [Meter::OWNERSHIP_SHARED, Meter::OWNERSHIP_APARTMENT], true)
                    && ! $buildingId
                ) {

                    $validator->errors()->add(
                        'building_id',
                        'Select a building for this meter.'
                    );
                }

                if ($this->boolean('is_shared') && ! $buildingId) {

                    $validator->errors()->add(
                        'building_id',
                        'Shared meters must belong to a building.'
                    );
                }

                if (
                    $ownershipType === Meter::OWNERSHIP_APARTMENT
                    && ! $apartmentId
                ) {

                    $validator->errors()->add(
                        'apartment_id',
                        'Select the unit this meter serves.'
                    );
                }

                if (
                    $ownershipType === Meter::OWNERSHIP_TENANT
                    && ! $tenantId
                ) {

                    $validator->errors()->add(
                        'tenant_id',
                        'Select the tenant billed for this meter.'
                    );
                }

                if ($ownershipType === Meter::OWNERSHIP_TENANT && $apartmentId) {

                    $validator->errors()->add(
                        'apartment_id',
                        'Tenant ownership uses the tenant field only — do not assign a unit.'
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