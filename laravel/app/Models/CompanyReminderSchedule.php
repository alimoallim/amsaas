<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReminderSchedule extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'days_before_due',
        'days_after_due',
        'channels',
        'is_active',
    ];

    protected $casts = [
        'days_before_due' => 'array',
        'days_after_due'  => 'array',
        'channels'        => 'array',
        'is_active'       => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
