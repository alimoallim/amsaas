<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementCharge extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'agreement_charges';

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

        'agreement_id',

        'charge_model_id',

        'charge_type_id',

        'custom_name',

        'override_amount',

        'override_percentage',

        'override_unit_rate',

        'quantity',

        'billing_start_date',

        'billing_end_date',

        'next_billing_date',

        'proration_enabled',

        'is_required',

        'is_taxable',

        'is_discountable',

        'is_suspended',

        'suspension_reason',

        'priority',

        'status',

        'notes',

        'metadata',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'override_amount' =>
            'decimal:2',

        'override_percentage' =>
            'decimal:4',

        'override_unit_rate' =>
            'decimal:6',

        'quantity' =>
            'decimal:4',

        'proration_enabled' =>
            'boolean',

        'is_required' =>
            'boolean',

        'is_taxable' =>
            'boolean',

        'is_discountable' =>
            'boolean',

        'is_suspended' =>
            'boolean',

        'priority' =>
            'integer',

        'metadata' =>
            'array',

        'billing_start_date' =>
            'date',

        'billing_end_date' =>
            'date',

        'next_billing_date' =>
            'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT =
        'draft';

    const STATUS_ACTIVE =
        'active';

    const STATUS_SUSPENDED =
        'suspended';

    const STATUS_TERMINATED =
        'terminated';

    const STATUS_ARCHIVED =
        'archived';

    const STATUSES = [

        self::STATUS_DRAFT,

        self::STATUS_ACTIVE,

        self::STATUS_SUSPENDED,

        self::STATUS_TERMINATED,

        self::STATUS_ARCHIVED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(
            Agreement::class
        );
    }

    public function chargeModel(): BelongsTo
    {
        return $this->belongsTo(
            ChargeModel::class
        );
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(
            ChargeType::class
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

    public function isActive(): bool
    {
        return $this->status ===
            self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->is_suspended;
    }

    public function hasOverrideAmount(): bool
    {
        return !is_null(
            $this->override_amount
        );
    }

    public function hasOverrideRate(): bool
    {
        return !is_null(
            $this->override_unit_rate
        );
    }

    public function supportsProration(): bool
    {
        return $this->proration_enabled;
    }

    public function isCurrentlyBillable(): bool
    {
        if (
            !$this->isActive()
            ||
            $this->isSuspended()
        ) {

            return false;
        }

        $today =
            now()->toDateString();

        return (

            $this->billing_start_date <= $today
            &&
            (
                !$this->billing_end_date
                ||
                $this->billing_end_date >= $today
            )
        );
    }
}