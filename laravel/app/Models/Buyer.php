<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToCompany; 

class Buyer extends Model
{
    use HasFactory, HasUuids, SoftDeletes,BelongsToCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function saleAgreements(): HasMany
    {
        return $this->hasMany(SaleAgreement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}