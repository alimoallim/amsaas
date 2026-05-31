<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeType extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'charge_types';

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

        'code',

        'name',

        'short_name',

        'description',

        'category',

        'billing_behavior',

        'calculation_method',

        'billing_frequency',

        'financial_classification',

        'default_currency',

        'default_amount',

        'default_percentage',

        'is_recurring',

        'is_metered',

        'requires_meter_reading',

        'is_taxable',

        'is_refundable',

        'allow_manual_override',

        'allow_proration',

        'allow_discount',

        'allow_penalty',

        'allow_adjustment',

        'auto_generate',

        'affects_occupancy',

        'ledger_account_code',

        'sort_order',

        'status',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'default_amount' =>
            'decimal:2',

        'default_percentage' =>
            'decimal:4',

        'is_recurring' =>
            'boolean',

        'is_metered' =>
            'boolean',

        'requires_meter_reading' =>
            'boolean',

        'is_taxable' =>
            'boolean',

        'is_refundable' =>
            'boolean',

        'allow_manual_override' =>
            'boolean',

        'allow_proration' =>
            'boolean',

        'allow_discount' =>
            'boolean',

        'allow_penalty' =>
            'boolean',

        'allow_adjustment' =>
            'boolean',

        'auto_generate' =>
            'boolean',

        'affects_occupancy' =>
            'boolean',

        'sort_order' =>
            'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    const CATEGORY_RENT =
        'rent';

    const CATEGORY_UTILITY =
        'utility';

    const CATEGORY_DEPOSIT =
        'deposit';

    const CATEGORY_PENALTY =
        'penalty';

    const CATEGORY_SERVICE =
        'service';

    const CATEGORY_TAX =
        'tax';

    const CATEGORY_DISCOUNT =
        'discount';

    const CATEGORY_ADJUSTMENT =
        'adjustment';

    const CATEGORY_MISCELLANEOUS =
        'miscellaneous';

    const CATEGORIES = [

        self::CATEGORY_RENT,

        self::CATEGORY_UTILITY,

        self::CATEGORY_DEPOSIT,

        self::CATEGORY_PENALTY,

        self::CATEGORY_SERVICE,

        self::CATEGORY_TAX,

        self::CATEGORY_DISCOUNT,

        self::CATEGORY_ADJUSTMENT,

        self::CATEGORY_MISCELLANEOUS,
    ];

    /*
    |--------------------------------------------------------------------------
    | Billing Behaviors
    |--------------------------------------------------------------------------
    */

    const BILLING_FIXED =
        'fixed';

    const BILLING_VARIABLE =
        'variable';

    const BILLING_METERED =
        'metered';

    const BILLING_PERCENTAGE =
        'percentage';

    const BILLING_TIERED =
        'tiered';

    const BILLING_FORMULA =
        'formula';

    const BILLING_BEHAVIORS = [

        self::BILLING_FIXED,

        self::BILLING_VARIABLE,

        self::BILLING_METERED,

        self::BILLING_PERCENTAGE,

        self::BILLING_TIERED,

        self::BILLING_FORMULA,
    ];

    /*
    |--------------------------------------------------------------------------
    | Calculation Methods
    |--------------------------------------------------------------------------
    */

    const CALCULATION_FIXED =
        'fixed_amount';

    const CALCULATION_PER_UNIT =
        'per_unit';

    const CALCULATION_PERCENTAGE =
        'percentage';

    const CALCULATION_FORMULA =
        'formula';

    const CALCULATION_TIERED =
        'tiered';

    const CALCULATION_METHODS = [

        self::CALCULATION_FIXED,

        self::CALCULATION_PER_UNIT,

        self::CALCULATION_PERCENTAGE,

        self::CALCULATION_FORMULA,

        self::CALCULATION_TIERED,
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
    | Financial Classification
    |--------------------------------------------------------------------------
    */

    const CLASSIFICATION_INCOME =
        'income';

    const CLASSIFICATION_LIABILITY =
        'liability';

    const CLASSIFICATION_EXPENSE =
        'expense';

    const CLASSIFICATION_CONTRA_REVENUE =
        'contra_revenue';

    const FINANCIAL_CLASSIFICATIONS = [

        self::CLASSIFICATION_INCOME,

        self::CLASSIFICATION_LIABILITY,

        self::CLASSIFICATION_EXPENSE,

        self::CLASSIFICATION_CONTRA_REVENUE,
    ];

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_ACTIVE =
        'active';

    const STATUS_INACTIVE =
        'inactive';

    const STATUS_ARCHIVED =
        'archived';

    const STATUSES = [

        self::STATUS_ACTIVE,

        self::STATUS_INACTIVE,

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

    public function isMetered(): bool
    {
        return $this->is_metered;
    }

    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    public function isTaxable(): bool
    {
        return $this->is_taxable;
    }

    public function isRefundable(): bool
    {
        return $this->is_refundable;
    }

    public function allowsProration(): bool
    {
        return $this->allow_proration;
    }

    public function isActive(): bool
    {
        return $this->status ===
            self::STATUS_ACTIVE;
    }
}