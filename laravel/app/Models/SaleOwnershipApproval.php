<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOwnershipApproval extends Model
{
    use BelongsToCompany, HasUuids;

    public const STEP_LEGAL = 'legal';

    public const STEP_FINANCE = 'finance';

    public const STEP_MANAGER = 'manager';

    /** @var list<string> */
    public const STEPS = [
        self::STEP_LEGAL,
        self::STEP_FINANCE,
        self::STEP_MANAGER,
    ];

    protected $fillable = [
        'company_id',
        'sale_agreement_id',
        'step',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function saleAgreement(): BelongsTo
    {
        return $this->belongsTo(SaleAgreement::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function stepLabel(string $step): string
    {
        return match ($step) {
            self::STEP_LEGAL => 'Legal',
            self::STEP_FINANCE => 'Finance',
            self::STEP_MANAGER => 'Manager',
            default => ucfirst($step),
        };
    }
}
