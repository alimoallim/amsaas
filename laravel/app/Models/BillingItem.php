<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'billing_items';

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

        'agreement_charge_id',

        'charge_model_id',

        'charge_type_id',

        'billing_run_id',

        'invoice_id',

        'billing_period_start',

        'billing_period_end',

        'billing_date',

        'due_date',

        'quantity',

        'unit_rate',

        'base_amount',

        'tax_amount',

        'discount_amount',

        'penalty_amount',

        'adjustment_amount',

        'subtotal_amount',

        'total_amount',

        'currency',

        'description',

        'calculation_snapshot',

        'metadata',

        'generated_at',

        'status',

        'posted_to_invoice',

        'posted_to_ledger',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'billing_period_start' =>
            'date',

        'billing_period_end' =>
            'date',

        'billing_date' =>
            'date',

        'due_date' =>
            'date',

        'generated_at' =>
            'datetime',

        'quantity' =>
            'decimal:4',

        'unit_rate' =>
            'decimal:6',

        'base_amount' =>
            'decimal:2',

        'tax_amount' =>
            'decimal:2',

        'discount_amount' =>
            'decimal:2',

        'penalty_amount' =>
            'decimal:2',

        'adjustment_amount' =>
            'decimal:2',

        'subtotal_amount' =>
            'decimal:2',

        'total_amount' =>
            'decimal:2',

        'posted_to_invoice' =>
            'boolean',

        'posted_to_ledger' =>
            'boolean',

        'calculation_snapshot' =>
            'array',

        'metadata' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT =
        'draft';

    const STATUS_PENDING =
        'pending';

    const STATUS_BILLED =
        'billed';

    const STATUS_INVOICED =
        'invoiced';

    const STATUS_PAID =
        'paid';

    const STATUS_VOIDED =
        'voided';

    const STATUS_CANCELLED =
        'cancelled';

    const STATUSES = [

        self::STATUS_DRAFT,

        self::STATUS_PENDING,

        self::STATUS_BILLED,

        self::STATUS_INVOICED,

        self::STATUS_PAID,

        self::STATUS_VOIDED,

        self::STATUS_CANCELLED,
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

    public function agreementCharge(): BelongsTo
    {
        return $this->belongsTo(
            AgreementCharge::class
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

    public function billingRun(): BelongsTo
    {
        return $this->belongsTo(
            BillingRun::class
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class
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

    public function isPending(): bool
    {
        return $this->status ===
            self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status ===
            self::STATUS_PAID;
    }

    public function isInvoiced(): bool
    {
        return $this->posted_to_invoice;
    }

    public function isLedgerPosted(): bool
    {
        return $this->posted_to_ledger;
    }

    public function canBeModified(): bool
    {
        return !

            $this->posted_to_invoice
            &&

            !$this->posted_to_ledger;
    }
}