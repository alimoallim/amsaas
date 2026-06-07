<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allocated = round((float) $this->allocations->sum('amount_allocated'), 2);
        $unallocated = round(max(0, (float) $this->amount - $allocated), 2);

        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'amount' => (float) $this->amount,
            'allocated_amount' => $allocated,
            'unallocated_amount' => $unallocated,
            'payment_date' => optional($this->payment_date)->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant?->id,
                'display_name' => $this->tenant?->full_display_name ?: null,
                'tenant_code' => $this->tenant?->tenant_code,
            ]),
            'allocations' => $this->whenLoaded('allocations', function () {
                return $this->allocations->map(fn ($allocation) => [
                    'id' => $allocation->id,
                    'monthly_invoice_id' => $allocation->monthly_invoice_id,
                    'amount_allocated' => (float) $allocation->amount_allocated,
                    'invoice_number' => $allocation->monthlyInvoice?->invoice_number,
                ]);
            }),
            'recorded_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
