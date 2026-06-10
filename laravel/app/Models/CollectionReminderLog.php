<?php

namespace App\Models;

use App\Enums\CollectionReminderType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionReminderLog extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'reminder_type' => CollectionReminderType::class,
        'sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    public function delinquencyFlag(): BelongsTo
    {
        return $this->belongsTo(DelinquencyFlag::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
