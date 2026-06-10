<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriodClose extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;

    protected $table = 'accounting_period_closes';

    protected $fillable = [
        'id',
        'company_id',
        'fiscal_year',
        'fiscal_month',
        'trial_balance_balanced',
        'total_debits',
        'total_credits',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'fiscal_month' => 'integer',
        'trial_balance_balanced' => 'boolean',
        'total_debits' => 'decimal:4',
        'total_credits' => 'decimal:4',
        'closed_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
