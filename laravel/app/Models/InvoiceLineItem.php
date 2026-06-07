<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
class InvoiceLineItem extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = ['id'];
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    /**
     * Dynamic reference linking back to source origins (e.g. Utility Readings, Fees).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}