<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'financed_amount',
        'is_installment_sale',
        'installment_months',
        'monthly_installment_amount',
        'plan_duration_years',
        'plan_duration_months',
        'ownership_transfer_date',
        'ownership_transferred',
        'broker_commission',
        'broker_name',
        'closing_date',
        'title_deed_issued',
        'special_terms',
        'completion_certificate_path',
        'ownership_transfer_certificate_path',
        'title_deed_number',
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

        'financed_amount' =>
            'decimal:2',

        'broker_commission' =>
            'decimal:2',

        'is_installment_sale' =>
            'boolean',

        'installment_months' =>
            'integer',

        'plan_duration_years' =>
            'integer',

        'plan_duration_months' =>
            'integer',

        'monthly_installment_amount' =>
            'decimal:2',

        'ownership_transfer_date' =>
            'date',

        'ownership_transferred' =>
            'boolean',

        'closing_date' =>
            'date',

        'title_deed_issued' =>
            'boolean',
    ];

    public function isPriceLocked(): bool
    {
        $status = $this->agreement?->status;

        return $status !== null
            && ! in_array($status, [
                Agreement::STATUS_DRAFT,
                Agreement::STATUS_PENDING_APPROVAL,
            ], true);
    }

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

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(SalePaymentAllocation::class, 'sale_agreement_id');
    }

    public function depositApplications(): HasMany
    {
        return $this->hasMany(SaleDepositApplication::class, 'sale_agreement_id');
    }

    public function installmentSchedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class, 'sale_agreement_id')
            ->orderBy('installment_number');
    }

    public function ownershipApprovals(): HasMany
    {
        return $this->hasMany(SaleOwnershipApproval::class, 'sale_agreement_id');
    }

    public function ownershipHistory(): HasMany
    {
        return $this->hasMany(ApartmentOwnershipHistory::class, 'sale_agreement_id');
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
        return $this->balanceDue() <= 0.009;
    }

    public function isPaymentPlan(): bool
    {
        return (bool) $this->is_installment_sale;
    }

    public function depositAppliedAmount(): float
    {
        if ($this->relationLoaded('depositApplications')) {
            return round((float) $this->depositApplications->sum('amount'), 2);
        }

        return round((float) $this->depositApplications()->sum('amount'), 2);
    }

    public function paidAmount(): float
    {
        $cashPaid = $this->relationLoaded('paymentAllocations')
            ? round((float) $this->paymentAllocations->sum('amount_allocated'), 2)
            : round((float) $this->paymentAllocations()->sum('amount_allocated'), 2);

        return round($cashPaid + $this->depositAppliedAmount(), 2);
    }

    public function financedAmountValue(): float
    {
        if ($this->financed_amount !== null) {
            return round((float) $this->financed_amount, 2);
        }

        return max(0, round((float) $this->sale_price - (float) ($this->down_payment ?? 0), 2));
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->sale_price - $this->paidAmount(), 2));
    }

    public function progressPercent(): float
    {
        $price = (float) $this->sale_price;
        if ($price <= 0) {
            return 0;
        }

        return round(min(100, ($this->paidAmount() / $price) * 100), 2);
    }

    public function financedCollectedAmount(): float
    {
        $down = (float) ($this->down_payment ?? 0);

        return max(0, round(min($this->financedAmountValue(), $this->paidAmount() - $down), 2));
    }

    public function financedBalanceDue(): float
    {
        return max(0, round($this->financedAmountValue() - $this->financedCollectedAmount(), 2));
    }

    public function isPaymentPlanOverdue(): bool
    {
        if (! $this->isPaymentPlan() || $this->balanceDue() <= 0.009) {
            return false;
        }

        $endDate = $this->agreement?->end_date;

        return $endDate !== null && $endDate->isPast();
    }

    public function paymentPlanDaysRemaining(): ?int
    {
        $endDate = $this->agreement?->end_date;
        if (! $endDate) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($endDate, false);
    }

    /** @return array<string, mixed> */
    public function paymentPlanSummary(): array
    {
        return [
            'is_payment_plan' => $this->isPaymentPlan(),
            'financed_amount' => $this->financedAmountValue(),
            'financed_collected' => $this->financedCollectedAmount(),
            'financed_balance' => $this->financedBalanceDue(),
            'running_balance' => $this->balanceDue(),
            'total_paid' => $this->paidAmount(),
            'progress_percent' => $this->progressPercent(),
            'plan_duration_years' => $this->plan_duration_years,
            'plan_duration_months' => $this->plan_duration_months,
            'is_term_overdue' => $this->isPaymentPlanOverdue(),
            'days_remaining' => $this->paymentPlanDaysRemaining(),
        ];
    }

    public function hasInstallmentSchedule(): bool
    {
        if ($this->relationLoaded('installmentSchedules')) {
            return $this->installmentSchedules->isNotEmpty();
        }

        return $this->installmentSchedules()->exists();
    }

    public function installmentPaidAmount(): float
    {
        if ($this->relationLoaded('installmentSchedules')) {
            return round((float) $this->installmentSchedules->sum('paid_amount'), 2);
        }

        return round((float) $this->installmentSchedules()->sum('paid_amount'), 2);
    }

    public function installmentOutstanding(): float
    {
        if ($this->relationLoaded('installmentSchedules')) {
            return round(
                (float) $this->installmentSchedules->sum(
                    fn (InstallmentSchedule $row) => $row->balanceDue(),
                ),
                2,
            );
        }

        $rows = $this->installmentSchedules()->get();

        return round(
            (float) $rows->sum(fn (InstallmentSchedule $row) => $row->balanceDue()),
            2,
        );
    }

    public function installmentScheduleSummary(): array
    {
        $rows = $this->relationLoaded('installmentSchedules')
            ? $this->installmentSchedules
            : $this->installmentSchedules()->get();

        $nextDue = $rows
            ->filter(fn (InstallmentSchedule $row) => $row->balanceDue() > 0.009)
            ->sortBy('due_date')
            ->first();

        $paidCount = $rows->filter(
            fn (InstallmentSchedule $row) => $row->effectiveStatus() === InstallmentSchedule::STATUS_PAID,
        )->count();

        return [
            'is_generated' => $rows->isNotEmpty(),
            'installment_count' => $rows->count(),
            'total_scheduled' => round((float) $rows->sum('amount'), 2),
            'total_paid' => round((float) $rows->sum('paid_amount'), 2),
            'outstanding' => round(
                (float) $rows->sum(fn (InstallmentSchedule $row) => $row->balanceDue()),
                2,
            ),
            'paid_count' => $paidCount,
            'pending_count' => $rows->count() - $paidCount,
            'next_due_date' => $nextDue?->due_date?->format('Y-m-d'),
            'next_due_amount' => $nextDue?->balanceDue(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isInstallmentAgreement(): bool
    {
        return $this->isPaymentPlan();
    }

    public function requiresOwnershipTransfer(): bool
    {
        return !

            empty(
                $this->ownership_transfer_date
            );
    }

    /** @return list<string> */
    public function ownershipPendingSteps(): array
    {
        if ($this->agreement?->status !== Agreement::STATUS_COMPLETED || $this->ownership_transferred) {
            return [];
        }

        $approved = $this->relationLoaded('ownershipApprovals')
            ? $this->ownershipApprovals->pluck('step')->all()
            : $this->ownershipApprovals()->pluck('step')->all();

        return array_values(array_diff(SaleOwnershipApproval::STEPS, $approved));
    }

    public function canApproveOwnershipStep(string $step): bool
    {
        return $this->agreement?->status === Agreement::STATUS_COMPLETED
            && ! $this->ownership_transferred
            && in_array($step, $this->ownershipPendingSteps(), true);
    }
}