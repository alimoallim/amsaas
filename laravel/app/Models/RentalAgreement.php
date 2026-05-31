<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RentalAgreement extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */
    protected $table =
        'rental_agreements';
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

        'monthly_rent',

        'security_deposit',

        'payment_due_day',

        'billing_cycle',

        'includes_water',

        'includes_electricity',

        'includes_internet',

        'auto_renew',

        'renewal_notice_days',

        'late_fee_amount',

        'grace_period_days',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'monthly_rent' =>
            'decimal:2',

        'security_deposit' =>
            'decimal:2',

        'late_fee_amount' =>
            'decimal:2',

        'payment_due_day' =>
            'integer',

        'renewal_notice_days' =>
            'integer',

        'grace_period_days' =>
            'integer',

        'includes_water' =>
            'boolean',

        'includes_electricity' =>
            'boolean',

        'includes_internet' =>
            'boolean',

        'auto_renew' =>
            'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Billing Cycles
    |--------------------------------------------------------------------------
    */

    const BILLING_MONTHLY =
        'monthly';

    const BILLING_QUARTERLY =
        'quarterly';

    const BILLING_SEMI_ANNUAL =
        'semi_annual';

    const BILLING_ANNUAL =
        'annual';

    const BILLING_CYCLES = [

        self::BILLING_MONTHLY,

        self::BILLING_QUARTERLY,

        self::BILLING_SEMI_ANNUAL,

        self::BILLING_ANNUAL,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(

            Agreement::class,

            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTotalMoveInAmountAttribute(): float
    {
        return (

            (float) $this->monthly_rent
            +
            (float) $this->security_deposit
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function hasUtilityIncluded(): bool
    {
        return (

            $this->includes_water
            ||
            $this->includes_electricity
            ||
            $this->includes_internet
        );
    }

    public function isAutoRenewEnabled(): bool
    {
        return $this->auto_renew;
    }
}