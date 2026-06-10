<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentSchedule extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

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

    public function saleAgreement(): BelongsTo
    {
        return $this->belongsTo(SaleAgreement::class);
    }

    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    public function canAcceptPayment(): bool
    {
        return $this->balanceDue() > 0.009;
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }

    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_PAID || $this->balanceDue() <= 0.009) {
            return self::STATUS_PAID;
        }

        if ((float) $this->paid_amount > 0.009) {
            return $this->due_date?->isPast()
                ? self::STATUS_OVERDUE
                : self::STATUS_PARTIALLY_PAID;
        }

        if ($this->due_date?->isPast()) {
            return self::STATUS_OVERDUE;
        }

        return self::STATUS_PENDING;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIALLY_PAID => 'Partially paid',
            self::STATUS_OVERDUE => 'Overdue',
            default => 'Pending',
        };
    }
}
