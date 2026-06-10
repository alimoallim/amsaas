<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Accounting\PostingRuleService;
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
        $postingRules = app(PostingRuleService::class);
        $receiptAccountCode = $postingRules->resolveReceiptAccountCode($this->resource);
        $receiptAccountName = $postingRules->receiptAccountName(
            (string) $this->company_id,
            $receiptAccountCode,
        );

        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'amount' => (float) $this->amount,
            'allocated_amount' => $allocated,
            'unallocated_amount' => $unallocated,
            'payment_date' => optional($this->payment_date)->format('Y-m-d'),
            'payment_purpose' => $this->payment_purpose ?? 'rent',
            'payment_method' => $this->payment_method,
            'agreement_id' => $this->agreement_id,
            'agreement' => $this->whenLoaded('agreement', fn () => [
                'id' => $this->agreement?->id,
                'agreement_number' => $this->agreement?->agreement_number,
            ]),
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant?->id,
                'display_name' => $this->tenant?->full_display_name ?: null,
                'tenant_code' => $this->tenant?->tenant_code,
            ]),
            'buyer' => $this->whenLoaded('buyer', fn () => [
                'id' => $this->buyer?->id,
                'display_name' => $this->buyer?->full_name ?: null,
            ]),
            'posting' => [
                'receipt_account_code' => $receiptAccountCode,
                'receipt_account_name' => $receiptAccountName,
                'receipt_account_overridden' => filled($this->receipt_account_code),
            ],
            'journal_entries' => $this->when(
                $this->relationLoaded('journalEntries'),
                fn () => JournalEntryResource::collection($this->journalEntries),
            ),
            'allocations' => $this->whenLoaded('allocations', function () {
                return $this->allocations->map(fn ($allocation) => [
                    'id' => $allocation->id,
                    'monthly_invoice_id' => $allocation->monthly_invoice_id,
                    'amount_allocated' => (float) $allocation->amount_allocated,
                    'invoice_number' => $allocation->monthlyInvoice?->invoice_number,
                ]);
            }),
            'recorded_by' => $this->whenLoaded('recordedBy', fn () => [
                'id' => $this->recordedBy?->id,
                'name' => $this->recordedBy?->name,
            ]),
            'recorded_at' => $this->created_at?->toIso8601String(),
            'controls' => [
                'can_view_receipt' => true,
            ],
        ];
    }
}
