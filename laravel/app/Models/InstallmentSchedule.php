<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToCompany; 

class InstallmentSchedule extends Model
{
    use HasFactory, HasUuids,BelongsToCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'installment_number' => 'integer',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'principal' => 'decimal:2',
        'interest' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    // Relationships
    public function saleAgreement(): BelongsTo
    {
        return $this->belongsTo(SaleAgreement::class);
    }
}
