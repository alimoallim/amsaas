<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDepositApplication extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function saleAgreement(): BelongsTo
    {
        return $this->belongsTo(SaleAgreement::class);
    }

    public function saleReservation(): BelongsTo
    {
        return $this->belongsTo(SaleReservation::class);
    }

    public function depositPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'deposit_payment_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
