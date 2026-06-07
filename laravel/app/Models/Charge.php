<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Charge extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table = 'charges';

    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    protected $keyType = 'string';

    public $incrementing = false;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'id',
        'uuid',

        'charge_number',
        'reference_number',

        'company_id',
        'building_id',
        'apartment_id',
        'tenant_id',
        'rental_agreement_id',

        'billing_cycle_id',
        'charge_type_id',
        'charge_model_id',
        'invoice_id',
        'meter_reading_id',

        'parent_charge_id',

        'category',
        'billing_strategy',
        'status',

        'currency',

        'description',
        'notes',

        'quantity',
        'unit_rate',

        'subtotal_amount',
        'tax_amount',
        'discount_amount',
        'total_amount',

        'meter_previous_reading',
        'meter_current_reading',
        'meter_consumption',

        'company_name_snapshot',
        'building_name_snapshot',
        'apartment_label_snapshot',
        'tenant_name_snapshot',
        'agreement_number_snapshot',

        'service_period_start',
        'service_period_end',

        'charged_at',
        'approved_at',
        'invoiced_at',
        'paid_at',
        'reversed_at',

        'generated_by',
        'approved_by',
        'reversed_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'quantity' => 'decimal:4',
        'unit_rate' => 'decimal:4',

        'subtotal_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',

        'meter_previous_reading' => 'decimal:4',
        'meter_current_reading' => 'decimal:4',
        'meter_consumption' => 'decimal:4',

        'service_period_start' => 'date',
        'service_period_end' => 'date',

        'charged_at' => 'datetime',
        'approved_at' => 'datetime',
        'invoiced_at' => 'datetime',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    public const CATEGORY_RENT = 'rent';

    public const CATEGORY_UTILITY = 'utility';

    public const CATEGORY_SERVICE_FEE = 'service_fee';

    public const CATEGORY_MAINTENANCE = 'maintenance';

    public const CATEGORY_PENALTY = 'penalty';

    public const CATEGORY_TAX = 'tax';

    public const CATEGORY_DEPOSIT = 'deposit';

    public const CATEGORY_CUSTOM = 'custom';

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_INVOICED = 'invoiced';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_OVERDUE = 'overdue';

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

    public function rentalAgreement(): BelongsTo
    {
        return $this->belongsTo(
            RentalAgreement::class
        );
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(
            ChargeType::class
        );
    }

    public function chargeModel(): BelongsTo
    {
        return $this->belongsTo(
            ChargeModel::class
        );
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(
            MeterReading::class
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            MonthlyInvoice::class,
            'invoice_id'
        );
    }

    public function parentCharge(): BelongsTo
    {
        return $this->belongsTo(
            Charge::class,
            'parent_charge_id'
        );
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(
            Charge::class,
            'parent_charge_id'
        );
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'generated_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reversed_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isInvoiced(): bool
    {
        return $this->status === self::STATUS_INVOICED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE;
    }

    public function isUtilityCharge(): bool
    {
        return $this->category === self::CATEGORY_UTILITY;
    }

    public function canBeInvoiced(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_APPROVED,
            ]
        );
    }

    public function canBeReversed(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_APPROVED,
                self::STATUS_INVOICED,
                self::STATUS_PARTIALLY_PAID,
            ]
        );
    }
}