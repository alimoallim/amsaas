<?php

namespace App\Models;

use App\Enums\DelinquencyEscalationStage;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionNotice extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'notice_type' => DelinquencyEscalationStage::class,
    ];

    public function delinquencyFlag(): BelongsTo
    {
        return $this->belongsTo(DelinquencyFlag::class);
    }

    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
