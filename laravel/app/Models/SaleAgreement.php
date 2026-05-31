<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleAgreement extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table =
        'sale_agreements';

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

        'sale_price',

        'down_payment',

        'is_installment_sale',

        'installment_months',

        'ownership_transfer_date',

        'broker_commission',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'sale_price' =>
            'decimal:2',

        'down_payment' =>
            'decimal:2',

        'broker_commission' =>
            'decimal:2',

        'is_installment_sale' =>
            'boolean',

        'installment_months' =>
            'integer',

        'ownership_transfer_date' =>
            'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(

            Agreement::class,

            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getRemainingBalanceAttribute(): float
    {
        return (

            (float) $this->sale_price
            -
            (float) $this->down_payment
        );
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_balance <= 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isInstallmentAgreement(): bool
    {
        return $this->is_installment_sale;
    }

    public function requiresOwnershipTransfer(): bool
    {
        return !

            empty(
                $this->ownership_transfer_date
            );
    }
}