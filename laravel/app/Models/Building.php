<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }
}