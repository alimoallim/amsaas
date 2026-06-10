<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApartmentOwnershipHistory extends Model
{
    use BelongsToCompany, HasUuids;

    protected $table = 'apartment_ownership_history';

    protected $fillable = [
        'company_id',
        'apartment_id',
        'buyer_id',
        'sale_agreement_id',
        'transfer_date',
        'title_deed_number',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function saleAgreement(): BelongsTo
    {
        return $this->belongsTo(SaleAgreement::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
