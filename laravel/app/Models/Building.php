<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder; // Add this import
use App\Traits\LogsActivity; 
use App\Models\Traits\BelongsToCompany;
class Building extends Model
{
    use HasFactory, HasUuids, SoftDeletes,BelongsToCompany;

    protected $fillable = [

    'company_id',

    'name',

    'code',

    'type',

    'address',

    'city',

    'country',

    'timezone',

    'operating_currency',

    'total_units',

    'total_floors',

    'description',

    'is_active',
];

    /**
     * The "booted" method of the model.
     * This is where we enforce absolute Company Data Isolation.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('company_isolation', function (Builder $builder) {
            // Only apply this scope if a user is logged in (e.g., via web or API)
            // This prevents console commands or background jobs from failing 
            // if they need to access all buildings, while keeping HTTP requests 100% secure.
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('buildings.company_id', auth()->user()->company_id);
            }
        });
    }

    // ... rest of your relationships (company, apartments) ...
}