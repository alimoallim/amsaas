<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeModel extends Model
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
        'charge_models';

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

        'charge_type_id',

        'code',

        'name',

        'description',

        'currency',

        'base_amount',

        'minimum_amount',

        'maximum_amount',

        'unit_rate',

        'percentage_rate',

        'billing_frequency',

        'pricing_strategy',

        'meter_type',

        'tier_configuration',

        'formula_expression',

        'proration_enabled',

        'grace_period_days',

        'late_fee_enabled',

        'late_fee_type',

        'late_fee_value',

        'taxable',

        'tax_rate',

        'effective_from',

        'effective_to',

        'auto_generate',

        'requires_approval',

        'status',

        'sort_order',

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

        'base_amount' =>
            'decimal:2',

        'minimum_amount' =>
            'decimal:2',

        'maximum_amount' =>
            'decimal:2',

        'unit_rate' =>
            'decimal:6',

        'percentage_rate' =>
            'decimal:4',

        'late_fee_value' =>
            'decimal:2',

        'tax_rate' =>
            'decimal:4',

        'tier_configuration' =>
            'array',

        'metadata' =>
            'array',

        'proration_enabled' =>
            'boolean',

        'late_fee_enabled' =>
            'boolean',

        'taxable' =>
            'boolean',

        'auto_generate' =>
            'boolean',

        'requires_approval' =>
            'boolean',

        'grace_period_days' =>
            'integer',

        'sort_order' =>
            'integer',

        'effective_from' =>
            'date',

        'effective_to' =>
            'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Pricing Strategies
    |--------------------------------------------------------------------------
    */

    const STRATEGY_FIXED =
        'fixed';

    /** Monthly rent taken from the linked rental agreement (not stored on this model). */
    const STRATEGY_AGREEMENT_RENT =
        'agreement_rent';

    /** Flat recurring fee; amount is set on each agreement charge line. */
    const STRATEGY_FLAT_FEE =
        'flat_fee';

    const STRATEGY_METERED =
        'metered';

    const STRATEGY_PERCENTAGE =
        'percentage';

    const STRATEGY_TIERED =
        'tiered';

    const STRATEGY_FORMULA =
        'formula';

    const STRATEGIES = [

        self::STRATEGY_AGREEMENT_RENT,

        self::STRATEGY_FLAT_FEE,

        self::STRATEGY_FIXED,

        self::STRATEGY_METERED,

        self::STRATEGY_PERCENTAGE,

        self::STRATEGY_TIERED,

        self::STRATEGY_FORMULA,
    ];

    /*
    |--------------------------------------------------------------------------
    | Billing Frequencies
    |--------------------------------------------------------------------------
    */

    const FREQUENCY_ONE_TIME =
        'one_time';

    const FREQUENCY_DAILY =
        'daily';

    const FREQUENCY_WEEKLY =
        'weekly';

    const FREQUENCY_MONTHLY =
        'monthly';

    const FREQUENCY_QUARTERLY =
        'quarterly';

    const FREQUENCY_YEARLY =
        'yearly';

    const BILLING_FREQUENCIES = [

        self::FREQUENCY_ONE_TIME,

        self::FREQUENCY_DAILY,

        self::FREQUENCY_WEEKLY,

        self::FREQUENCY_MONTHLY,

        self::FREQUENCY_QUARTERLY,

        self::FREQUENCY_YEARLY,
    ];

    /*
    |--------------------------------------------------------------------------
    | Meter Types
    |--------------------------------------------------------------------------
    */

    const METER_ELECTRICITY =
        'electricity';

    const METER_WATER =
        'water';

    const METER_GAS =
        'gas';

    const METER_TYPES = [

        self::METER_ELECTRICITY,

        self::METER_WATER,

        self::METER_GAS,
    ];

    /*
    |--------------------------------------------------------------------------
    | Late Fee Types
    |--------------------------------------------------------------------------
    */

    const LATE_FEE_FIXED =
        'fixed';

    const LATE_FEE_PERCENTAGE =
        'percentage';

    const LATE_FEE_TYPES = [

        self::LATE_FEE_FIXED,

        self::LATE_FEE_PERCENTAGE,
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

    const STATUS_INACTIVE =
        'inactive';

    const STATUS_ARCHIVED =
        'archived';

    const STATUSES = [

        self::STATUS_DRAFT,

        self::STATUS_ACTIVE,

        self::STATUS_INACTIVE,

        self::STATUS_ARCHIVED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function isMetered(): bool
    {
        return $this->pricing_strategy ===
            self::STRATEGY_METERED;
    }

    public function isTiered(): bool
    {
        return $this->pricing_strategy ===
            self::STRATEGY_TIERED;
    }

    public function isFixed(): bool
    {
        return $this->pricing_strategy ===
            self::STRATEGY_FIXED;
    }

    public function usesAgreementRent(): bool
    {
        return $this->pricing_strategy ===
            self::STRATEGY_AGREEMENT_RENT;
    }

    public function usesFlatFee(): bool
    {
        return in_array(
            $this->pricing_strategy,
            [
                self::STRATEGY_FLAT_FEE,
                self::STRATEGY_FIXED,
            ],
            true
        );
    }

    public function supportsProration(): bool
    {
        return $this->proration_enabled;
    }

    public function hasLateFee(): bool
    {
        return $this->late_fee_enabled;
    }

    public function isCurrentlyEffective(): bool
    {
        $today = now()->toDateString();

        return (

            $this->effective_from <= $today
            &&
            (
                !$this->effective_to
                ||
                $this->effective_to >= $today
            )
        );
    }
}