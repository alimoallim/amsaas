<?php

namespace App\Http\Resources\Api\V1;

use App\Models\InstallmentSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InstallmentSchedule */
class InstallmentScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $effectiveStatus = $this->resource->effectiveStatus();
        $balanceDue = $this->resource->balanceDue();

        return [
            'id' => $this->id,
            'installment_number' => $this->installment_number,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'amount' => $this->amount,
            'principal' => $this->principal,
            'interest' => $this->interest,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $balanceDue,
            'status' => [
                'value' => $effectiveStatus,
                'label' => InstallmentSchedule::statusLabel($effectiveStatus),
                'stored' => $this->status,
            ],
            'paid_at' => $this->paid_at?->format('Y-m-d'),
            'notes' => $this->notes,
            'is_overdue' => $effectiveStatus === InstallmentSchedule::STATUS_OVERDUE,

            'monthly_invoice_id' => $this->monthly_invoice_id,

            'controls' => [
                'can_record_payment' => $this->canAcceptPayment(),
            ],
        ];
    }
}
