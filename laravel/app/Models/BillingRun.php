<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BillingRun extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'billing_runs';

    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    protected $keyType =
        'string';

    public $incrementing =
        false;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'id',

        'company_id',

        'run_number',

        'name',

        'description',

        'billing_frequency',

        'billing_period_start',

        'billing_period_end',

        'scheduled_at',

        'execution_started_at',

        'execution_completed_at',

        'status',

        'total_agreements_processed',

        'total_billing_items_generated',

        'total_successful_items',

        'total_failed_items',

        'subtotal_amount',

        'tax_amount',

        'penalty_amount',

        'discount_amount',

        'total_amount',

        'currency',

        'failure_count',

        'success_count',

        'error_summary',

        'notes',

        'metadata',

        'is_dry_run',

        'is_locked',

        'executed_by',

        'approved_by',

        'approved_at',

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'billing_period_start' =>
            'date',

        'billing_period_end' =>
            'date',

        'scheduled_at' =>
            'datetime',

        'execution_started_at' =>
            'datetime',

        'execution_completed_at' =>
            'datetime',

        'approved_at' =>
            'datetime',

        'subtotal_amount' =>
            'decimal:2',

        'tax_amount' =>
            'decimal:2',

        'penalty_amount' =>
            'decimal:2',

        'discount_amount' =>
            'decimal:2',

        'total_amount' =>
            'decimal:2',

        'total_agreements_processed' =>
            'integer',

        'total_billing_items_generated' =>
            'integer',

        'total_successful_items' =>
            'integer',

        'total_failed_items' =>
            'integer',

        'failure_count' =>
            'integer',

        'success_count' =>
            'integer',

        'is_dry_run' =>
            'boolean',

        'is_locked' =>
            'boolean',

        'metadata' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT =
        'draft';

    const STATUS_PENDING =
        'pending';

    const STATUS_RUNNING =
        'running';

    const STATUS_COMPLETED =
        'completed';

    const STATUS_FAILED =
        'failed';

    const STATUS_PARTIALLY_COMPLETED =
        'partially_completed';

    const STATUS_CANCELLED =
        'cancelled';

    const STATUSES = [

        self::STATUS_DRAFT,

        self::STATUS_PENDING,

        self::STATUS_RUNNING,

        self::STATUS_COMPLETED,

        self::STATUS_FAILED,

        self::STATUS_PARTIALLY_COMPLETED,

        self::STATUS_CANCELLED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Billing Frequencies
    |--------------------------------------------------------------------------
    */

    const FREQUENCY_DAILY =
        'daily';

    const FREQUENCY_WEEKLY =
        'weekly';

    const FREQUENCY_MONTHLY =
        'monthly';

    const FREQUENCY_QUARTERLY =
        'quarterly';

    const FREQUENCY_YEARLY =
        'yearly';

    const FREQUENCIES = [

        self::FREQUENCY_DAILY,

        self::FREQUENCY_WEEKLY,

        self::FREQUENCY_MONTHLY,

        self::FREQUENCY_QUARTERLY,

        self::FREQUENCY_YEARLY,
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

    public function billingItems(): HasMany
    {
        return $this->hasMany(
            BillingItem::class
        );
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'executed_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(

            User::class,

            'approved_by'
        );
    }

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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isRunning(): bool
    {
        return $this->status ===
            self::STATUS_RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this->status ===
            self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status ===
            self::STATUS_FAILED;
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function canExecute(): bool
    {
        return in_array(

            $this->status,

            [

                self::STATUS_DRAFT,

                self::STATUS_PENDING,
            ]
        );
    }

    public function executionDurationInSeconds(): ?int
    {
        if (
            !$this->execution_started_at
            ||
            !$this->execution_completed_at
        ) {

            return null;
        }

        return $this->execution_started_at
            ->diffInSeconds(

                $this->execution_completed_at
            );
    }
}