<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'type',
        'description',
        'is_system',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [
        self::TYPE_ASSET,
        self::TYPE_LIABILITY,
        self::TYPE_EQUITY,
        self::TYPE_REVENUE,
        self::TYPE_EXPENSE,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /** Standard posting account codes (Pillar 3). */
    public const CODE_CASH = '1110';

    public const CODE_BANK = '1115';

    public const CODE_MOBILE_MONEY = '1116';

    public const CODE_CHEQUE_IN_TRANSIT = '1117';

    public const CODE_ACCOUNTS_RECEIVABLE = '1120';

    public const CODE_CUSTOMER_DEPOSITS_PAYABLE = '2120';

    /** @deprecated Use CODE_CUSTOMER_DEPOSITS_PAYABLE */
    public const CODE_TENANT_DEPOSITS_PAYABLE = self::CODE_CUSTOMER_DEPOSITS_PAYABLE;

    public const CODE_DEFERRED_REVENUE = '2130';

    /** @deprecated Use CODE_DEFERRED_REVENUE */
    public const CODE_UNEARNED_RENT = self::CODE_DEFERRED_REVENUE;

    public const CODE_RENTAL_INCOME = '4100';

    public const CODE_UTILITY_INCOME = '4110';

    public const CODE_SERVICE_INCOME = '4140';

    public const CODE_SALE_INCOME = '4150';

    /** @var array<string, string> Legacy 6.1 seed codes → standard codes */
    public const LEGACY_CODE_ALIASES = [
        '1000' => self::CODE_CASH,
        '1200' => self::CODE_ACCOUNTS_RECEIVABLE,
        '2100' => self::CODE_UNEARNED_RENT,
        '2200' => self::CODE_TENANT_DEPOSITS_PAYABLE,
        '4000' => self::CODE_RENTAL_INCOME,
        '4100' => self::CODE_UTILITY_INCOME,
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
