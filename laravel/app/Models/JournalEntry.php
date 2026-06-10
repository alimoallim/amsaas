<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use LogsActivity;

    protected $table = 'journal_entries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'entry_number',
        'entry_date',
        'posting_date',
        'currency_code',
        'description',
        'source_type',
        'source_id',
        'fiscal_year',
        'fiscal_month',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posting_date' => 'date',
        'fiscal_year' => 'integer',
        'fiscal_month' => 'integer',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
    ];

    public const STATUS_POSTED = 'posted';

    public const SOURCE_INVOICE_ISSUED = 'monthly_invoice_issued';

    public const SOURCE_PAYMENT_ALLOCATION = 'payment_allocation';

    public const SOURCE_SALE_PAYMENT_ALLOCATION = 'sale_payment_allocation';

    public const SOURCE_CUSTOMER_DEPOSIT = 'customer_deposit';

    public const SOURCE_RENTAL_SECURITY_DEPOSIT = 'rental_security_deposit';

    public const SOURCE_RENTAL_DEPOSIT_REFUND = 'rental_deposit_refund';

    public const SOURCE_RENTAL_DEPOSIT_APPLICATION = 'rental_deposit_application';

    public const SOURCE_SALE_DEPOSIT_APPLICATION = 'sale_deposit_application';

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
