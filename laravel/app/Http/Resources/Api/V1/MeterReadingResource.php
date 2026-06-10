<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeterReadingResource extends JsonResource
{
    /**
     * Transform resource into array.
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

            /*
            |--------------------------------------------------------------------------
            | Meter Information
            |--------------------------------------------------------------------------
            */

            'meter' => [

                'id' =>

                    $this->meter?->id,

                'meter_number' =>

                    $this->meter?->meter_number,

                'serial_number' =>

                    $this->meter?->serial_number,

                'utility_type' => [

                    'value' =>

                        $this->meter?->utility_type,

                    'label' =>

                        optional(
                            $this->meter
                        )?->utilityLabel(),
                ],

                'measurement_unit' =>

                    $this->meter?->measurement_unit,

                'status' =>

                    $this->meter?->status,
            ],

            /*
            |--------------------------------------------------------------------------
            | Property Information
            |--------------------------------------------------------------------------
            */

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

                'floor_number' =>

                    $this->apartment?->floor_number,
            ],

            /*
            |--------------------------------------------------------------------------
            | Reading Information
            |--------------------------------------------------------------------------
            */

            'reading' => [

                'reading_date' =>

                    optional(
                        $this->reading_date
                    )?->toDateString(),

                'previous_reading' =>

                    (float)
                    $this->previous_reading,

                'current_reading' =>

                    (float)
                    $this->current_reading,

                'consumption' =>

                    (float)
                    $this->consumption,

                'formatted_consumption' =>

                    number_format(

                        (float)
                        $this->consumption,

                        2
                    )

                    . ' '

                    . (
                        $this->meter?->measurement_unit
                        ?? ''
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Reading Type
            |--------------------------------------------------------------------------
            */

            'reading_type' => [

                'value' =>

                    $this->reading_type,

                'label' =>

                    $this->readingTypeLabel(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Reading Source
            |--------------------------------------------------------------------------
            */

            'reading_source' => [

                'value' =>

                    $this->reading_source,

                'label' =>

                    str(
                        $this->reading_source
                    )

                    ->replace(
                        '_',
                        ' '
                    )

                    ->title(),
            ],

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

                'color' =>

                    $this->resolveStatusColor(),

                'is_approved' =>

                    $this->isApproved(),

                'is_rejected' =>

                    $this->isRejected(),

                'requires_review' =>

                    $this->requiresReview(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Anomaly Detection
            |--------------------------------------------------------------------------
            */

            'anomaly' => [

                'detected' =>

                    (bool)
                    $this->anomaly_detected,

                'reason' =>

                    $this->anomaly_reason,

                'severity' =>

                    $this->resolveAnomalySeverity(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Reader Information
            |--------------------------------------------------------------------------
            */

            'reader' => [

                'id' =>

                    $this->reader?->id,

                'name' =>

                    $this->reader?->name
                    ??
                    $this->reader_name,
            ],

            /*
            |--------------------------------------------------------------------------
            | Approval Information
            |--------------------------------------------------------------------------
            */

            'approval' => [

                'approved_by' => [

                    'id' =>

                        $this->approver?->id,

                    'name' =>

                        $this->approver?->name,
                ],

                'approved_at' =>

                    optional(
                        $this->approved_at
                    )?->toDateTimeString(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes & Attachments
            |--------------------------------------------------------------------------
            */

            'notes' =>

                $this->notes,

            'attachment_path' =>

                $this->attachment_path,

            /*
            |--------------------------------------------------------------------------
            | Operational Controls
            |--------------------------------------------------------------------------
            */

            'controls' => [

                'can_approve' =>

                    $this->canBeApproved(),

                'can_edit' =>

                    $this->canBeEdited(),

                'can_reject' =>

                    !$this->isRejected(),

                'has_anomaly' =>

                    $this->hasAnomaly(),
            ],

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
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Status Color
    |--------------------------------------------------------------------------
    */

    protected function resolveStatusColor(): string
    {
        return match (
            $this->status
        ) {

            'approved' =>
                'green',

            'verified' =>
                'blue',

            'draft' =>
                'yellow',

            'rejected' =>
                'red',

            default =>
                'gray',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Anomaly Severity
    |--------------------------------------------------------------------------
    */

    protected function resolveAnomalySeverity(): ?string
    {
        if (
            !$this->anomaly_detected
        ) {

            return null;
        }

        if (
            str(
                $this->anomaly_reason
            )->contains('spike')
        ) {

            return 'high';
        }

        return 'medium';
    }
}