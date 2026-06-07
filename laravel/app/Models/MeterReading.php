<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'meter_readings';

    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    protected $keyType =
        'string';

    public $incrementing =
        false;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'id',

        'company_id',

        'meter_id',

        'building_id',

        'apartment_id',

        'reading_date',

        'previous_reading',

        'current_reading',

        'consumption',

        'reading_type',

        'reading_source',

        'reader_name',

        'reader_user_id',

        'status',

        'anomaly_detected',

        'anomaly_reason',

        'notes',

        'attachment_path',

        'metadata',

        'approved_by',

        'approved_at',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'reading_date' =>
            'date',

        'previous_reading' =>
            'decimal:4',

        'current_reading' =>
            'decimal:4',

        'consumption' =>
            'decimal:4',

        'anomaly_detected' =>
            'boolean',

        'approved_at' =>
            'datetime',

        'metadata' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Reading Types
    |--------------------------------------------------------------------------
    */

    const TYPE_ACTUAL =
        'actual';

    const TYPE_ESTIMATED =
        'estimated';

    const TYPE_ADJUSTED =
        'adjusted';

    const TYPE_IMPORTED =
        'imported';

    const READING_TYPES = [

        self::TYPE_ACTUAL,

        self::TYPE_ESTIMATED,

        self::TYPE_ADJUSTED,

        self::TYPE_IMPORTED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Reading Sources
    |--------------------------------------------------------------------------
    */

    const SOURCE_MANUAL =
        'manual';

    const SOURCE_SMART_METER =
        'smart_meter';

    const SOURCE_API =
        'api';

    const SOURCE_CSV_IMPORT =
        'csv_import';

    const READING_SOURCES = [

        self::SOURCE_MANUAL,

        self::SOURCE_SMART_METER,

        self::SOURCE_API,

        self::SOURCE_CSV_IMPORT,
    ];

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT =
        'draft';

    const STATUS_VERIFIED =
        'verified';

    const STATUS_APPROVED =
        'approved';

    const STATUS_REJECTED =
        'rejected';

    const STATUSES = [

        self::STATUS_DRAFT,

        self::STATUS_VERIFIED,

        self::STATUS_APPROVED,

        self::STATUS_REJECTED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function meter(): BelongsTo
    {
        return $this->belongsTo(
            Meter::class
        );
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(
            Building::class
        );
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(
            Apartment::class
        );
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'reader_user_id'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'approved_by'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'updated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->status ===
            self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status ===
            self::STATUS_REJECTED;
    }

    public function isEstimated(): bool
    {
        return $this->reading_type ===
            self::TYPE_ESTIMATED;
    }

    public function hasAnomaly(): bool
    {
        return $this->anomaly_detected;
    }

    /*
    |--------------------------------------------------------------------------
    | Operational Indicators
    |--------------------------------------------------------------------------
    */

    public function requiresReview(): bool
    {
        return

            $this->anomaly_detected
            ||

            in_array(

                $this->status,

                [

                    self::STATUS_DRAFT,

                    self::STATUS_REJECTED,
                ]
            );
    }

    public function canBeApproved(): bool
    {
        return

            $this->status ===
            self::STATUS_VERIFIED;
    }

    /*
    |--------------------------------------------------------------------------
    | Consumption Validation
    |--------------------------------------------------------------------------
    */

    public function hasNegativeConsumption(): bool
    {
        return

            $this->consumption < 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function statusLabel(): string
    {
        return str(
            $this->status
        )

        ->replace(
            '_',
            ' '
        )

        ->title();
    }

    public function readingTypeLabel(): string
    {
        return str(
            $this->reading_type
        )

        ->replace(
            '_',
            ' '
        )

        ->title();
    }
}