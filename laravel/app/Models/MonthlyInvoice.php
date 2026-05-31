<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Traits\BelongsToCompany; 

class MonthlyInvoice extends Model
{
    use HasFactory, HasUuids, SoftDeletes,BelongsToCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'billing_year' => 'integer',
        'billing_month' => 'integer',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_rent' => 'decimal:2',
        'subtotal_utilities' => 'decimal:2',
        'subtotal_services' => 'decimal:2',
        'subtotal_installment' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total_amount' => 'decimal:2', // Database Generated Column (Read-Only)
        'balance_due' => 'decimal:2',  // Database Generated Column (Read-Only)
        'finalized_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the owning contract model (RentalAgreement or SaleAgreement).
     */
    public function contract(): MorphTo
    {
        return $this->morphTo();
    }
}
