<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apartment extends Model
{
    use HasFactory,
        HasUuids,
        SoftDeletes,
        BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table = 'apartments';

    protected $keyType = 'string';

    public $incrementing = false;
    

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'company_id',
        'building_id',
        'unit_number',
        'floor',
        'bedrooms',
        'bathrooms',
        'area_sqm',
        'property_type',
        /*
        |--------------------------------------------------------------------------
        | Listing Strategy
        |--------------------------------------------------------------------------
        */
        'listing_type',
        /*
        |--------------------------------------------------------------------------
        | Inventory Lifecycle
        |--------------------------------------------------------------------------
        */
        'inventory_status',

        /*
        |--------------------------------------------------------------------------
        | Commercial
        |--------------------------------------------------------------------------
        */

        'market_rent_price',

        'market_sale_price',

        'security_deposit',

        'currency',

        /*
        |--------------------------------------------------------------------------
        | Features
        |--------------------------------------------------------------------------
        */

        'has_balcony',

        'has_parking',

        'has_storage',

        'is_furnished',

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        'notes',

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'floor' => 'integer',

        'bedrooms' => 'integer',

        'bathrooms' => 'integer',

        'area_sqm' => 'decimal:2',

        'market_rent_price' => 'decimal:2',

        'market_sale_price' => 'decimal:2',

        'security_deposit' => 'decimal:2',

        'has_balcony' => 'boolean',

        'has_parking' => 'boolean',

        'has_storage' => 'boolean',

        'is_furnished' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Listing Types
    |--------------------------------------------------------------------------
    */

    const LISTING_TYPE_RENTAL = 'rental';

    const LISTING_TYPE_SALE = 'sale';

    const LISTING_TYPE_HYBRID = 'hybrid';

    const LISTING_TYPE_NOT_LISTED = 'not_listed';

    const LISTING_TYPES = [

        self::LISTING_TYPE_RENTAL,

        self::LISTING_TYPE_SALE,

        self::LISTING_TYPE_HYBRID,

        self::LISTING_TYPE_NOT_LISTED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Inventory Statuses
    |--------------------------------------------------------------------------
    */

    const STATUS_AVAILABLE = 'available';

    const STATUS_OCCUPIED = 'occupied';

    const STATUS_RESERVED = 'reserved';

    const STATUS_UNDER_CONTRACT = 'under_contract';

    const STATUS_SOLD = 'sold';

    const STATUS_MAINTENANCE = 'maintenance';

    const STATUS_BLOCKED = 'blocked';

    const INVENTORY_STATUSES = [

        self::STATUS_AVAILABLE,

        self::STATUS_OCCUPIED,

        self::STATUS_RESERVED,

        self::STATUS_UNDER_CONTRACT,

        self::STATUS_SOLD,

        self::STATUS_MAINTENANCE,

        self::STATUS_BLOCKED,
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

    public function building(): BelongsTo
    {
        return $this->belongsTo(
            Building::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Future Agreements
    |--------------------------------------------------------------------------
    */

    public function agreements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Agreement::class, 'apartment_id')
            ->where('status', Agreement::STATUS_ACTIVE)
            ->latest('start_date');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable(
        Builder $query
    ): Builder {

        return $query->where(
            'inventory_status',
            self::STATUS_AVAILABLE
        );
    }

    public function scopeRental(
        Builder $query
    ): Builder {

        return $query->whereIn(

            'listing_type',

            [
                self::LISTING_TYPE_RENTAL,
                self::LISTING_TYPE_HYBRID,
            ]
        );
    }

    public function scopeSale(
        Builder $query
    ): Builder {

        return $query->whereIn(

            'listing_type',

            [
                self::LISTING_TYPE_SALE,
                self::LISTING_TYPE_HYBRID,
            ]
        );
    }

    public function scopeInBuilding(
        Builder $query,
        string $buildingId
    ): Builder {

        return $query->where(
            'building_id',
            $buildingId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    */

    public function canBeRented(): bool
    {
        return in_array(

            $this->listing_type,

            [
                self::LISTING_TYPE_RENTAL,
                self::LISTING_TYPE_HYBRID,
            ]
        )
        &&
        in_array(

            $this->inventory_status,

            [
                self::STATUS_AVAILABLE,
                self::STATUS_RESERVED,
            ]
        );
    }

    public function canBeSold(): bool
    {
        return in_array(

            $this->listing_type,

            [
                self::LISTING_TYPE_SALE,
                self::LISTING_TYPE_HYBRID,
            ]
        )
        &&
        in_array(

            $this->inventory_status,

            [
                self::STATUS_AVAILABLE,
                self::STATUS_RESERVED,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getEffectivePriceAttribute(): ?string
    {
        return $this->listing_type
            === self::LISTING_TYPE_SALE

            ? $this->market_sale_price

            : $this->market_rent_price;
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->inventory_status
            === self::STATUS_AVAILABLE;
    }
}