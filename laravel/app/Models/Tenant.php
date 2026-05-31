<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Traits\BelongsToCompany;

class Tenant extends Model
{
    use HasFactory,
        HasUuids,
        SoftDeletes,
        BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    protected $keyType = 'string';

    public $incrementing = false;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'company_id',

        'tenant_code',
        'tenant_type',

        'first_name',
        'middle_name',
        'last_name',

        'display_name',

        'company_name',

        'email',
        'phone',
        'alternate_phone',

        'national_id',
        'passport_number',
        'tax_number',

        'nationality',
        'date_of_birth',

        'gender',
        'occupation',

        'country',
        'city',
        'address',
        'postal_code',

        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',

        'status',

        'notes',

        'created_by',
        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'date_of_birth' => 'date',
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

    public function rentalAgreements(): HasMany
    {
        return $this->hasMany(
            RentalAgreement::class
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullDisplayNameAttribute(): string
    {
        return $this->display_name
            ?? trim(

                collect([

                    $this->first_name,
                    $this->middle_name,
                    $this->last_name,

                ])->filter()->implode(' ')
            );
    }
}