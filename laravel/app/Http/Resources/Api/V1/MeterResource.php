<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;

class MeterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Core Identity
            |--------------------------------------------------------------------------
            */

            'id' =>

                $this->id,

            'building_id' =>

                $this->building_id,

            'apartment_id' =>

                $this->apartment_id,

            'tenant_id' =>

                $this->tenant_id,

            'meter_number' =>

                $this->meter_number,

            'serial_number' =>

                $this->serial_number,

            /*
            |--------------------------------------------------------------------------
            | Utility Information
            |--------------------------------------------------------------------------
            */

            'utility_type' => [

                'value' =>

                    $this->utility_type,

                'label' =>

                    $this->utilityLabel(),
            ],

            'ownership_type' => [

                'value' =>

                    $this->ownership_type,

                'label' =>

                    $this->ownershipLabel(),
            ],

            'meter_type' => [

                'value' =>

                    $this->meter_type,

                'label' =>

                    $this->meterTypeLabel(),
            ],

            'measurement_unit' =>

                $this->measurement_unit,

            /*
            |--------------------------------------------------------------------------
            | Operational Status
            |--------------------------------------------------------------------------
            */

            'status' => [

                'value' =>

                    $this->status,

                'label' =>

                    $this->statusLabel(),

                'is_active' =>

                    $this->isActive(),

                'is_faulty' =>

                    $this->isFaulty(),

                'is_operational' =>

                    $this->isOperational(),

                'requires_attention' =>

                    $this->requiresAttention(),

                'is_decommissioned' =>

                    $this->isDecommissioned(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Smart Meter Features
            |--------------------------------------------------------------------------
            */

            'smart_features' => [

                'is_smart_meter' =>

                    $this->isSmartMeter(),

                'supports_remote_reading' =>

                    $this->supportsRemoteReading(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Readings
            |--------------------------------------------------------------------------
            */

            'readings' => [

                'initial_reading' =>

                    (float)
                    $this->initial_reading,

                'current_reading' =>

                    (float)
                    $this->current_reading,

                'last_reading_at' =>

                    optional(
                        $this->last_reading_at
                    )?->toDateTimeString(),

                'previous_reading_value' =>

                    $this->previousReadingValue(),

                'multiplier_factor' =>

                    (float)
                    $this->multiplier_factor,
            ],

            /*
            |--------------------------------------------------------------------------
            | Meter Ownership Hierarchy
            |--------------------------------------------------------------------------
            */

            'company' => [

                'id' =>

                    $this->company?->id,

                'name' =>

                    $this->company?->name,
            ],

            'building' => [

                'id' =>

                    $this->building?->id,

                'name' =>

                    $this->building?->name,
            ],

            'apartment' => [

                'id' =>

                    $this->apartment?->id,

                'unit_number' =>

                    $this->apartment?->unit_number,

                'floor' =>

                    $this->apartment?->floor_number,
            ],

            'tenant' => [

                'id' =>

                    $this->tenant?->id,

                'name' =>

                    $this->tenant?->full_display_name

                    ?? $this->tenant?->display_name,
            ],

            /*
            |--------------------------------------------------------------------------
            | Meter Location
            |--------------------------------------------------------------------------
            */

            'location' => [

                'description' =>

                    $this->location_description,
            ],

            /*
            |--------------------------------------------------------------------------
            | Manufacturer Information
            |--------------------------------------------------------------------------
            */

            'manufacturer' => [

                'name' =>

                    $this->manufacturer,

                'model_number' =>

                    $this->model_number,
            ],

            /*
            |--------------------------------------------------------------------------
            | Lifecycle Dates
            |--------------------------------------------------------------------------
            */

            'lifecycle' => [

                'installation_date' =>

                    optional(
                        $this->installation_date
                    )?->toDateString(),

                'decommissioned_at' =>

                    optional(
                        $this->decommissioned_at
                    )?->toDateTimeString(),

                'last_maintenance_at' =>

                    optional(
                        $this->last_maintenance_at
                    )?->toDateTimeString(),

                'last_inspected_at' =>

                    optional(
                        $this->last_inspected_at
                    )?->toDateTimeString(),

                'inspection_due_date' =>

                    optional(
                        $this->inspection_due_date
                    )?->toDateString(),

                'maintenance_required' =>

                    $this->maintenance_required,

                'inspection_due' =>

                    $this->isInspectionDue(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Replacement Lifecycle
            |--------------------------------------------------------------------------
            */

            'replacement' => [

                'replacement_meter_id' =>

                    $this->replacement_meter_id,

                'replacement_meter' =>

                    $this->when(

                        $this->replacementMeter,

                        [

                            'id' =>

                                $this->replacementMeter?->id,

                            'meter_number' =>

                                $this->replacementMeter?->meter_number,
                        ]
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Operational Indicators
            |--------------------------------------------------------------------------
            */

            'operational_indicators' => [

                'is_shared' =>

                    $this->isShared(),

                'can_receive_readings' =>

                    $this->canReceiveReadings(),

                'can_generate_billing' =>

                    $this->canGenerateBilling(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Latest Reading Snapshot
            |--------------------------------------------------------------------------
            */

            'latest_reading' =>

                $this->when(

                    $this->relationLoaded(
                        'readings'
                    ),

                    function () {

                        $latest =
                            $this->latestReading();

                        if (!$latest) {

                            return null;
                        }

                        return [

                            'id' =>

                                $latest->id,

                            'reading_date' =>

                                optional(
                                    $latest->reading_date
                                )?->toDateString(),

                            'current_reading' =>

                                (float)
                                $latest->current_reading,

                            'consumption' =>

                                (float)
                                $latest->consumption,

                            'status' =>

                                $latest->status,
                        ];
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Operational Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>

                $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            'metadata' =>

                $this->metadata,

            /*
            |--------------------------------------------------------------------------
            | Audit Information
            |--------------------------------------------------------------------------
            */

            'audit' => [

                'created_at' =>

                    optional(
                        $this->created_at
                    )?->toDateTimeString(),

                'updated_at' =>

                    optional(
                        $this->updated_at
                    )?->toDateTimeString(),

                'deleted_at' =>

                    optional(
                        $this->deleted_at
                    )?->toDateTimeString(),

                'created_by' => [

                    'id' =>

                        $this->creator?->id,

                    'name' =>

                        $this->creator?->name,
                ],

                'updated_by' => [

                    'id' =>

                        $this->updater?->id,

                    'name' =>

                        $this->updater?->name,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Frontend Controls
            |--------------------------------------------------------------------------
            */

            'controls' => [

                'can_edit' =>

                    !$this->isDecommissioned(),

                'can_decommission' =>

                    !$this->isDecommissioned(),

                'can_replace' =>

                    !$this->isDecommissioned(),

                'can_activate' =>

                    $this->status !==
                    'active',

                'can_receive_readings' =>

                    $this->canReceiveReadings(),
            ],
        ];
    }
}