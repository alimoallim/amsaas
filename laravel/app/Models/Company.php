<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [

        'name',

        'email',

        'phone',

        'address',

        'city',

        'country',

        'registration_number',

        'tax_number',

        'currency_code',

        'logo_path',

        'is_active',
    ];

    /**
     * Type casting
     */
    protected $casts = [

        'is_active' => 'boolean',
    ];

    /**
     * Core Relationships
     */

    public function users(): HasMany
    {
        return $this->hasMany(
            User::class
        );
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(
            Building::class
        );
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(
            Tenant::class
        );
    }
}