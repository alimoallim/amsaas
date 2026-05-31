<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agreement extends Model
{
    use HasFactory,
        HasUuids,
        SoftDeletes,
        BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Table Configuration
    |--------------------------------------------------------------------------
    */

    protected $table = 'agreements';

    protected $keyType = 'string';

    public $incrementing = false;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'company_id',

        'agreement_number',

        'agreement_type',

        'apartment_id',

        'tenant_id',

        'status',

        'start_date',

        'end_date',

        'signed_at',

        'contract_amount',

        'currency',

        'approved_by',

        'approved_at',

        'terminated_at',

        'terminated_by',

        'termination_reason',

        'contract_file_path',

        'notes',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'start_date' =>
            'date',

        'end_date' =>
            'date',

        'signed_at' =>
            'datetime',

        'approved_at' =>
            'datetime',

        'terminated_at' =>
            'datetime',

        'contract_amount' =>
            'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Agreement Types
    |--------------------------------------------------------------------------
    */

    public const TYPE_RENTAL =
        'rental';

    public const TYPE_SALE =
        'sale';

    public const TYPES = [

        self::TYPE_RENTAL,

        self::TYPE_SALE,
    ];

    /*
    |--------------------------------------------------------------------------
    | Agreement Statuses
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT =
        'draft';

    public const STATUS_PENDING_APPROVAL =
        'pending_approval';

    public const STATUS_APPROVED =
        'approved';

    public const STATUS_ACTIVE =
        'active';

    public const STATUS_COMPLETED =
        'completed';

    public const STATUS_TERMINATED =
        'terminated';

    public const STATUS_CANCELLED =
        'cancelled';

    public const STATUS_EXPIRED =
        'expired';

    public const STATUS_RENEWAL_PENDING =
        'renewal_pending';

    public const STATUS_RENEWED =
        'renewed';

    public const STATUS_DEFAULTED =
        'defaulted';

    public const STATUSES = [

        self::STATUS_DRAFT,

        self::STATUS_PENDING_APPROVAL,

        self::STATUS_APPROVED,

        self::STATUS_ACTIVE,

        self::STATUS_COMPLETED,

        self::STATUS_TERMINATED,

        self::STATUS_CANCELLED,

        self::STATUS_EXPIRED,

        self::STATUS_RENEWAL_PENDING,

        self::STATUS_RENEWED,

        self::STATUS_DEFAULTED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(
            Apartment::class
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Audit Relationships
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'updated_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'approved_by'
        );
    }

    public function terminator(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'terminated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Specialized Agreements
    |--------------------------------------------------------------------------
    */

    public function rentalAgreement(): HasOne
    {
        return $this->hasOne(

            RentalAgreement::class,

            'id'
        );
    }

    public function saleAgreement(): HasOne
    {
        return $this->hasOne(

            SaleAgreement::class,

            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRental(
        $query
    )
    {
        return $query->where(

            'agreement_type',

            self::TYPE_RENTAL
        );
    }

    public function scopeSale(
        $query
    )
    {
        return $query->where(

            'agreement_type',

            self::TYPE_SALE
        );
    }

    public function scopeActive(
        $query
    )
    {
        return $query->where(

            'status',

            self::STATUS_ACTIVE
        );
    }

    public function scopeDraft(
        $query
    )
    {
        return $query->where(

            'status',

            self::STATUS_DRAFT
        );
    }

    public function scopePendingApproval(
        $query
    )
    {
        return $query->where(

            'status',

            self::STATUS_PENDING_APPROVAL
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsActiveAttribute(): bool
    {
        return $this->status
            === self::STATUS_ACTIVE;
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status
            === self::STATUS_DRAFT;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status
            === self::STATUS_EXPIRED;
    }

    public function getIsRentalAttribute(): bool
    {
        return $this->agreement_type
            === self::TYPE_RENTAL;
    }

    public function getIsSaleAttribute(): bool
    {
        return $this->agreement_type
            === self::TYPE_SALE;
    }

    public function getAgreementTypeLabelAttribute(): string
    {
        return match (

            $this->agreement_type
        ) {

            self::TYPE_RENTAL =>
                'Rental Agreement',

            self::TYPE_SALE =>
                'Sale Agreement',

            default =>
                'Agreement',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return str(

            $this->status

        )->replace(
            '_',
            ' '
        )->title();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function canBeActivated(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_APPROVED,

                self::STATUS_RENEWED,
            ]
        );
    }

    public function canBeTerminated(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_ACTIVE,

                self::STATUS_DEFAULTED,
            ]
        );
    }

    public function canBeEdited(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_DRAFT,

                self::STATUS_PENDING_APPROVAL,
            ]
        );
    }

    public function isCurrentlyActive(): bool
    {
        return $this->status
            === self::STATUS_ACTIVE;
    }

    public function isExpiringSoon(
        int $days = 30
    ): bool {

        if (! $this->end_date) {

            return false;
        }

        return now()

            ->diffInDays(

                $this->end_date,

                false

            ) <= $days;
    }
}