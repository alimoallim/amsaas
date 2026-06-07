<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\MonthlyInvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $agreement = $this->contract_type === 'rental' && $this->relationLoaded('resolvedAgreement')
            ? $this->resolvedAgreement
            : null;

        $building = $this->apartment?->building;
        $status = MonthlyInvoiceStatus::tryFrom($this->status);

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'apartment_id' => $this->apartment_id,
            'contract_type' => $this->contract_type,
            'contract_id' => $this->contract_id,
            'billing_year' => $this->billing_year,
            'billing_month' => $this->billing_month,
            'billing_period' => sprintf('%04d-%02d', $this->billing_year, $this->billing_month),
            'issue_date' => optional($this->issue_date)->format('Y-m-d'),
            'due_date' => optional($this->due_date)->format('Y-m-d'),
            'subtotal_rent' => $this->subtotal_rent,
            'subtotal_utilities' => $this->subtotal_utilities,
            'subtotal_services' => $this->subtotal_services,
            'subtotal_installment' => $this->subtotal_installment,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $this->balance_due,
            'status' => $this->status,
            'notes' => $this->notes,
            'file_path' => $this->file_path,
            'dispatch_status' => $this->dispatch_status,
            'void_reason' => $this->void_reason,
            'voided_at' => optional($this->voided_at)->toIso8601String(),
            'finalized_at' => optional($this->finalized_at)->toIso8601String(),
            'apartment' => $this->whenLoaded('apartment', fn () => [
                'id' => $this->apartment?->id,
                'unit_number' => $this->apartment?->unit_number,
            ]),
            'building' => $building ? [
                'id' => $building->id,
                'name' => $building->name,
            ] : null,
            'tenant' => $agreement?->tenant ? [
                'id' => $agreement->tenant->id,
                'display_name' => $this->tenantDisplayName($agreement->tenant),
                'tenant_code' => $agreement->tenant->tenant_code ?? null,
            ] : null,
            'agreement' => $agreement ? [
                'id' => $agreement->id,
                'agreement_number' => $agreement->agreement_number ?? null,
            ] : null,
            'line_items' => InvoiceLineItemResource::collection(
                $this->whenLoaded('lineItems')
            ),
            'payment_allocations' => $this->whenLoaded('allocations', function () {
                return $this->allocations->map(fn ($allocation) => [
                    'id' => $allocation->id,
                    'amount_allocated' => $allocation->amount_allocated,
                    'payment' => $allocation->relationLoaded('payment') && $allocation->payment ? [
                        'id' => $allocation->payment->id,
                        'receipt_number' => $allocation->payment->receipt_number,
                        'payment_date' => optional($allocation->payment->payment_date)->format('Y-m-d'),
                        'payment_method' => $allocation->payment->payment_method,
                        'amount' => $allocation->payment->amount,
                    ] : null,
                ]);
            }),
            'controls' => [
                'can_issue' => $status?->isIssuable() ?? false,
                'can_void' => $status?->isVoidable() ?? false,
                'can_edit' => $status === MonthlyInvoiceStatus::Draft,
                'can_download' => ! empty($this->file_path),
                'can_resend_email' => $status
                    && ! in_array($status, [MonthlyInvoiceStatus::Draft, MonthlyInvoiceStatus::Cancelled], true)
                    && ! empty($this->file_path),
            ],
        ];
    }

    protected function tenantDisplayName($tenant): ?string
    {
        $display = trim((string) ($tenant->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        $composed = trim(collect([$tenant->first_name, $tenant->last_name])->filter()->implode(' '));

        return $composed !== '' ? $composed : null;
    }
}
