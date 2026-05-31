<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany; 

class UtilityReading extends Model
{
    use HasFactory, HasUuids,BelongsToCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'billing_year' => 'integer',
        'billing_month' => 'integer',
        'reading_start' => 'decimal:3',
        'reading_end' => 'decimal:3',
        'units_consumed' => 'decimal:3',
        'rate_per_unit' => 'decimal:4',
        'total_charge' => 'decimal:2', // Database Generated Column (Read-Only)
        'confirmed_at' => 'datetime',
    ];

    // Relationships
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function utilityRateConfig(): BelongsTo
    {
        return $this->belongsTo(UtilityRateConfig::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}