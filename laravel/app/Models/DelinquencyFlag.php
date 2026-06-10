<?php

namespace App\Models;

use App\Enums\DelinquencyEscalationStage;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelinquencyFlag extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'first_overdue_date' => 'date',
        'stage_updated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'escalation_stage' => DelinquencyEscalationStage::class,
    ];

    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    public function isActive(): bool
    {
        return $this->resolved_at === null;
    }
}
