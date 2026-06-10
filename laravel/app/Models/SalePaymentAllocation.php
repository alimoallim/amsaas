<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePaymentAllocation extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'company_id',
        'payment_id',
        'sale_agreement_id',
        'amount_allocated',
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function saleAgreement(): BelongsTo
    {
        return $this->belongsTo(SaleAgreement::class, 'sale_agreement_id');
    }
}
