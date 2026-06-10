<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buyer extends Model
{
    use BelongsToCompany, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'buyer_code',
        'tenant_id',
        'full_name',
        'email',
        'phone',
        'national_id',
        'nationality',
        'date_of_birth',
        'country',
        'city',
        'address',
        'postal_code',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function saleAgreements(): HasMany
    {
        return $this->hasMany(Agreement::class)
            ->where('agreement_type', Agreement::TYPE_SALE);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function saleReservations(): HasMany
    {
        return $this->hasMany(SaleReservation::class);
    }
}
