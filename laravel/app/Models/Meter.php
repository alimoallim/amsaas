<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany;

class Meter extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'meters';

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

        'building_id',

        'apartment_id',

        'tenant_id',

        'replacement_meter_id',

        'meter_number',

        'serial_number',

        'utility_type',

        'ownership_type',

        'meter_type',

        'measurement_unit',

        'location_description',

        'initial_reading',

        'current_reading',

        'multiplier_factor',

        'last_reading_at',

        'installation_date',

        'decommissioned_at',

        'inspection_due_date',

        'last_maintenance_at',

        'last_inspected_at',

        'status',

        'is_shared',

        'supports_remote_reading',

        'maintenance_required',

        'manufacturer',

        'model_number',

        'notes',

        'metadata',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden
    |--------------------------------------------------------------------------
    */

    protected $hidden = [

        'deleted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'initial_reading' =>
            'decimal:4',

        'current_reading' =>
            'decimal:4',

        'multiplier_factor' =>
            'decimal:4',

        'last_reading_at' =>
            'datetime',

        'installation_date' =>
            'date',

        'decommissioned_at' =>
            'datetime',

        'inspection_due_date' =>
            'date',

        'last_maintenance_at' =>
            'datetime',

        'last_inspected_at' =>
            'datetime',

        'is_shared' =>
            'boolean',

        'supports_remote_reading' =>
            'boolean',

        'maintenance_required' =>
            'boolean',

        'metadata' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Utility Types
    |--------------------------------------------------------------------------
    */

    const UTILITY_ELECTRICITY =
        'electricity';

    const UTILITY_WATER =
        'water';

    const UTILITY_GAS =
        'gas';

    const UTILITY_STEAM =
        'steam';

    const UTILITY_INTERNET =
        'internet';

    const UTILITY_SOLAR =
        'solar';

    const UTILITY_CHILLED_WATER =
        'chilled_water';

    const UTILITY_TYPES = [

        self::UTILITY_ELECTRICITY,

        self::UTILITY_WATER,

        self::UTILITY_GAS,

        self::UTILITY_STEAM,

        self::UTILITY_INTERNET,

        self::UTILITY_SOLAR,

        self::UTILITY_CHILLED_WATER,
    ];

    /*
    |--------------------------------------------------------------------------
    | Ownership Types
    |--------------------------------------------------------------------------
    */

    const OWNERSHIP_APARTMENT =
        'apartment';

    const OWNERSHIP_BUILDING =
        'building';

    const OWNERSHIP_SHARED =
        'shared';

    const OWNERSHIP_TENANT =
        'tenant';

    const OWNERSHIP_TYPES = [

        self::OWNERSHIP_APARTMENT,

        self::OWNERSHIP_BUILDING,

        self::OWNERSHIP_SHARED,

        self::OWNERSHIP_TENANT,
    ];

    /*
    |--------------------------------------------------------------------------
    | Meter Types
    |--------------------------------------------------------------------------
    */

    const TYPE_ANALOG =
        'analog';

    const TYPE_DIGITAL =
        'digital';

    const TYPE_SMART =
        'smart';

    const METER_TYPES = [

        self::TYPE_ANALOG,

        self::TYPE_DIGITAL,

        self::TYPE_SMART,
    ];

    /*
    |--------------------------------------------------------------------------
    | Measurement Units
    |--------------------------------------------------------------------------
    */

    const UNIT_KWH =
        'kwh';

    const UNIT_CUBIC_METER =
        'm3';

    const UNIT_GALLON =
        'gallon';

    const UNIT_LITER =
        'liter';

    const UNIT_GB =
        'gb';

    const UNIT_TON =
        'ton';

    const MEASUREMENT_UNITS = [

        self::UNIT_KWH,

        self::UNIT_CUBIC_METER,

        self::UNIT_GALLON,

        self::UNIT_LITER,

        self::UNIT_GB,

        self::UNIT_TON,
    ];

    /*
    |--------------------------------------------------------------------------
    | Meter Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_ACTIVE =
        'active';

    const STATUS_INACTIVE =
        'inactive';

    const STATUS_FAULTY =
        'faulty';

    const STATUS_UNDER_MAINTENANCE =
        'under_maintenance';

    const STATUS_REPLACED =
        'replaced';

    const STATUS_DECOMMISSIONED =
        'decommissioned';

    const STATUSES = [

        self::STATUS_ACTIVE,

        self::STATUS_INACTIVE,

        self::STATUS_FAULTY,

        self::STATUS_UNDER_MAINTENANCE,

        self::STATUS_REPLACED,

        self::STATUS_DECOMMISSIONED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function replacementMeter(): BelongsTo
    {
        return $this->belongsTo(

            Meter::class,

            'replacement_meter_id'
        );
    }

    public function readings(): HasMany
    {
        return $this->hasMany(
            MeterReading::class
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
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Meters billable to a rental agreement — only when explicitly assigned to the unit or tenant.
     * Building-level / unassigned shared meters are excluded.
     */
    public function scopeAssignedToAgreement(Builder $query, Agreement $agreement): Builder
    {
        return $query
            ->where('company_id', $agreement->company_id)
            ->where(function (Builder $q) use ($agreement) {
                $hasScope = false;

                if ($agreement->apartment_id) {
                    $q->where('apartment_id', $agreement->apartment_id);
                    $hasScope = true;
                }

                if ($agreement->tenant_id) {
                    $hasScope
                        ? $q->orWhere('tenant_id', $agreement->tenant_id)
                        : $q->where('tenant_id', $agreement->tenant_id);
                }

                if (! $hasScope) {
                    $q->whereRaw('1 = 0');
                }
            });
    }

    public function isAssignedToAgreement(Agreement $agreement): bool
    {
        if ($agreement->apartment_id && $this->apartment_id === $agreement->apartment_id) {
            return true;
        }

        if ($agreement->tenant_id && $this->tenant_id === $agreement->tenant_id) {
            return true;
        }

        return false;
    }

    public function canBillTenants(): bool
    {
        return filled($this->apartment_id) || filled($this->tenant_id);
    }

    /**
     * Meters serving a building: direct assignment, unit in building, or tenant leased in building.
     */
    public function scopeForBuilding(Builder $query, string $buildingId): Builder
    {
        return $query->where(function (Builder $q) use ($buildingId) {
            $q->where('building_id', $buildingId)
                ->orWhereHas('apartment', function (Builder $aq) use ($buildingId) {
                    $aq->where('building_id', $buildingId);
                })
                ->orWhereHas('tenant.agreements.apartment', function (Builder $apt) use ($buildingId) {
                    $apt->where('building_id', $buildingId);
                });
        });
    }

    public function scopeActive(
        Builder $query
    ): Builder {

        return $query->where(

            'status',

            self::STATUS_ACTIVE
        );
    }

    public function scopeFaulty(
        Builder $query
    ): Builder {

        return $query->where(

            'status',

            self::STATUS_FAULTY
        );
    }

    public function scopeOperational(
        Builder $query
    ): Builder {

        return $query->whereIn(

            'status',

            [

                self::STATUS_ACTIVE,

                self::STATUS_UNDER_MAINTENANCE,
            ]
        );
    }

    public function scopeSmart(
        Builder $query
    ): Builder {

        return $query->where(

            'meter_type',

            self::TYPE_SMART
        );
    }

    public function scopeShared(
        Builder $query
    ): Builder {

        return $query->where(

            'is_shared',

            true
        );
    }

    public function scopeRequiresMaintenance(
        Builder $query
    ): Builder {

        return $query->where(

            'maintenance_required',

            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Operational Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status ===
            self::STATUS_ACTIVE;
    }

    public function isFaulty(): bool
    {
        return $this->status ===
            self::STATUS_FAULTY;
    }

    public function isOperational(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_ACTIVE,

                self::STATUS_UNDER_MAINTENANCE,
            ]
        );
    }

    public function isShared(): bool
    {
        return (bool) $this->is_shared;
    }

    public function isSmartMeter(): bool
    {
        return $this->meter_type ===
            self::TYPE_SMART;
    }

    public function supportsRemoteReading(): bool
    {
        return $this->supports_remote_reading === true
            || ($this->supports_remote_reading === null && $this->isSmartMeter());
    }

    public function isDecommissioned(): bool
    {
        return $this->status ===
            self::STATUS_DECOMMISSIONED;
    }

    public function requiresAttention(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_FAULTY,

                self::STATUS_UNDER_MAINTENANCE,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Consumption Helpers
    |--------------------------------------------------------------------------
    */

    public function latestReading()
    {
        return $this->readings()

            ->latest(
                'reading_date'
            )

            ->first();
    }

    public function previousReadingValue(): float
    {
        $latest = $this->latestReading();

        if ($latest) {
            return (float) $latest->current_reading;
        }

        return (float) ($this->initial_reading ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function utilityLabel(): string
    {
        return str(

            $this->utility_type
        )

        ->replace(
            '_',
            ' '
        )

        ->title();
    }

    public function ownershipLabel(): string
    {
        return str(

            $this->ownership_type
        )

        ->replace(
            '_',
            ' '
        )

        ->title();
    }

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

    public function meterTypeLabel(): string
    {
        return str(

            $this->meter_type
        )

        ->replace(
            '_',
            ' '
        )

        ->title();
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle Helpers
    |--------------------------------------------------------------------------
    */

    public function canReceiveReadings(): bool
    {
        return $this->status ===
            self::STATUS_ACTIVE;
    }

    public function canGenerateBilling(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_ACTIVE,

                self::STATUS_UNDER_MAINTENANCE,
            ]
        );
    }

    public function isInspectionDue(): bool
    {
        if (
            !$this->inspection_due_date
        ) {

            return false;
        }

        return now()->greaterThanOrEqualTo(

            $this->inspection_due_date
        );
    }
}