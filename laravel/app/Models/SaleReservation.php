<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReservation extends Model
{
    use BelongsToCompany, HasFactory, HasUuids, SoftDeletes;

    public const STATUS_PENDING_DEPOSIT = 'pending_deposit';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CONVERTED = 'converted';

    /** @var list<string> */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING_DEPOSIT,
        self::STATUS_CONFIRMED,
    ];

    protected $fillable = [
        'company_id',
        'reservation_number',
        'apartment_id',
        'buyer_id',
        'deposit_amount',
        'reserved_price',
        'currency',
        'expiry_date',
        'status',
        'converted_agreement_id',
        'deposit_payment_id',
        'deposit_paid_at',
        'expired_at',
        'cancelled_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'reserved_price' => 'decimal:2',
        'expiry_date' => 'date',
        'deposit_paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function depositPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'deposit_payment_id');
    }

    public function convertedAgreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'converted_agreement_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isDepositPaid(): bool
    {
        return $this->deposit_payment_id !== null
            || $this->status === self::STATUS_CONFIRMED;
    }

    public function canConvertToContract(): bool
    {
        return $this->status === self::STATUS_CONFIRMED
            && $this->converted_agreement_id === null;
    }
}
