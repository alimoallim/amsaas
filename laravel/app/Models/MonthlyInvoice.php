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
use App\Traits\LogsActivity;

class MonthlyInvoice extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'billing_year' => 'integer',
        'billing_month' => 'integer',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_rent' => 'decimal:4',
        'subtotal_utilities' => 'decimal:4',
        'subtotal_services' => 'decimal:4',
        'subtotal_installment' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'finalized_at' => 'datetime',
        'voided_at' => 'datetime',
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
