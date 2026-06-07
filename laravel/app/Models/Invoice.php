<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated Use {@see MonthlyInvoice} for all billing and API flows (ADR 001).
 *             The `invoices` table remains for a future enterprise invoice engine only.
 */
class Invoice extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_WRITTEN_OFF = 'written_off';

    /*
    |--------------------------------------------------------------------------
    | Invoice Type Constants
    |--------------------------------------------------------------------------
    */
    public const TYPE_RENTAL = 'rental';
    public const TYPE_UTILITY = 'utility';
    public const TYPE_SERVICE_FEE = 'service_fee';
    public const TYPE_INSTALLMENT = 'installment';
    public const TYPE_PENALTY = 'penalty';
    public const TYPE_MIXED = 'mixed';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'building_id',
        'apartment_id',
        'tenant_id',
        'buyer_id',
        'rental_agreement_id',
        'sale_agreement_id',
        'invoice_number',
        'reference_number',
        'invoice_type',
        'status',
        'billing_period_start',
        'billing_period_end',
        'issue_date',
        'due_date',
        'paid_date',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'penalty_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'is_system_generated',
        'is_recurring',
        'is_locked',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'is_system_generated' => 'boolean',
        'is_recurring'        => 'boolean',
        'is_locked'           => 'boolean',
        'billing_period_start'=> 'date',
        'billing_period_end'  => 'date',
        'issue_date'          => 'date',
        'due_date'            => 'date',
        'paid_date'           => 'datetime',
        'subtotal'            => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'tax_amount'          => 'decimal:2',
        'penalty_amount'      => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'paid_amount'         => 'decimal:2',
        'balance_due'         => 'decimal:2',
        'exchange_rate'       => 'decimal:6',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function apartment(): BelongsTo { return $this->belongsTo(Apartment::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function buyer(): BelongsTo { return $this->belongsTo(Buyer::class); }
    public function rentalAgreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class); }
    public function saleAgreement(): BelongsTo { return $this->belongsTo(SaleAgreement::class); }
    
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    /*
    |--------------------------------------------------------------------------
    | Business Logic Helpers
    |--------------------------------------------------------------------------
    */
    
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->balance_due > 0;
    }
}