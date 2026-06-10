<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SaleOwnershipApproval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleAgreementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Core Agreement Identity
            |--------------------------------------------------------------------------
            */

            'id' =>

                $this->id,

            'agreement_number' =>

                $this->agreement
                    ?->agreement_number,

            'agreement_type' =>

                $this->agreement
                    ?->agreement_type,

            /*
            |--------------------------------------------------------------------------
            | Agreement Lifecycle
            |--------------------------------------------------------------------------
            */

            'status' => [

                'value' =>

                    $this->agreement
                        ?->status,

                'label' =>

                    $this->agreement
                        ?->status_label,

                'is_active' =>

                    $this->agreement
                        ?->is_active,

                'is_completed' =>

                    $this->agreement
                        ?->status
                        === 'completed',

                'is_cancelled' =>

                    $this->agreement
                        ?->status
                        === 'cancelled',
            ],

            /*
            |--------------------------------------------------------------------------
            | Apartment Information
            |--------------------------------------------------------------------------
            */

            'apartment' =>

                $this->when(

                    $this->agreement
                    ?->apartment,

                    fn () => [

                        'id' =>

                            $this->agreement
                                ?->apartment
                                ?->id,

                        'unit_number' =>

                            $this->agreement
                                ?->apartment
                                ?->unit_number,

                        'building_id' =>

                            $this->agreement
                                ?->apartment
                                ?->building_id,

                        'building' =>

                            $this->agreement
                                ?->apartment
                                ?->relationLoaded('building')
                            && $this->agreement?->apartment?->building
                                ? [
                                    'id' => $this->agreement->apartment->building->id,
                                    'name' => $this->agreement->apartment->building->name,
                                ]
                                : null,

                        'listing_type' =>

                            $this->agreement
                                ?->apartment
                                ?->listing_type,

                        'inventory_status' =>

                            $this->agreement
                                ?->apartment
                                ?->inventory_status,

                        'bedrooms' =>

                            $this->agreement
                                ?->apartment
                                ?->bedrooms,

                        'bathrooms' =>

                            $this->agreement
                                ?->apartment
                                ?->bathrooms,

                        'area_sqm' =>

                            $this->agreement
                                ?->apartment
                                ?->area_sqm,
                    ]
                ),

            /*
            |--------------------------------------------------------------------------
            | Buyer / Tenant Information
            |--------------------------------------------------------------------------
            */

            'buyer' =>

                $this->when(

                    $this->agreement
                    ?->buyer,

                    fn () => [

                        'id' =>

                            $this->agreement
                                ?->buyer
                                ?->id,

                        'buyer_code' =>

                            $this->agreement
                                ?->buyer
                                ?->buyer_code,

                        'full_name' =>

                            $this->agreement
                                ?->buyer
                                ?->full_name,

                        'email' =>

                            $this->agreement
                                ?->buyer
                                ?->email,

                        'phone' =>

                            $this->agreement
                                ?->buyer
                                ?->phone,
                    ]
                ),

            /*
            |--------------------------------------------------------------------------
            | Agreement Dates
            |--------------------------------------------------------------------------
            */

            'dates' => [

                'start_date' =>

                    optional(

                        $this->agreement
                            ?->start_date

                    )->format('Y-m-d'),

                'end_date' =>

                    optional(

                        $this->agreement
                            ?->end_date

                    )->format('Y-m-d'),

                'signed_at' =>

                    optional(

                        $this->agreement
                            ?->signed_at

                    )->toDateTimeString(),

                'approved_at' =>

                    optional(

                        $this->agreement
                            ?->approved_at

                    )->toDateTimeString(),

                'ownership_transfer_date' =>

                    optional(

                        $this->ownership_transfer_date

                    )->format('Y-m-d'),

                'closing_date' =>

                    optional(

                        $this->closing_date

                    )->format('Y-m-d'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Sale Financials
            |--------------------------------------------------------------------------
            */

            'financials' => [

                'sale_price' =>

                    $this->sale_price,

                'down_payment' =>

                    $this->down_payment,

                'financed_amount' =>

                    $this->isPaymentPlan()
                        ? $this->financedAmountValue()
                        : null,

                'paid_amount' =>

                    $this->paidAmount(),

                'balance_due' =>

                    $this->balanceDue(),

                'remaining_balance' =>

                    $this->balanceDue(),

                'progress_percent' =>

                    $this->progressPercent(),

                'contract_amount' =>

                    $this->agreement
                        ?->contract_amount,

                'currency' =>

                    $this->agreement
                        ?->currency,

                'broker_commission' =>

                    $this->broker_commission,

                'price_locked' =>

                    $this->isPriceLocked(),

                'deposit_applied' =>

                    $this->depositAppliedAmount(),

                'deposit_ledger' =>

                    app(\App\Services\Sales\SaleDepositService::class)->summary(
                        (string) $this->agreement?->company_id,
                        (string) $this->id,
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment Plan (agreement-based financed sale)
            |--------------------------------------------------------------------------
            */

            'payment_plan' =>

                $this->isPaymentPlan()
                    ? array_merge($this->paymentPlanSummary(), [
                        'mode' => 'payment_plan',
                        'start_date' => optional($this->agreement?->start_date)->format('Y-m-d'),
                        'end_date' => optional($this->agreement?->end_date)->format('Y-m-d'),
                    ])
                    : [
                        'mode' => 'cash',
                    ],

            'installments' => [

                'is_installment_sale' =>

                    (bool) $this->is_installment_sale,

                'is_payment_plan' =>

                    $this->isPaymentPlan(),

                'installment_months' =>

                    $this->installment_months,

                'plan_duration_years' =>

                    $this->plan_duration_years,

                'plan_duration_months' =>

                    $this->plan_duration_months,

                'monthly_installment_amount' =>

                    $this->monthly_installment_amount,

                'summary' =>

                    $this->isPaymentPlan()
                        ? $this->paymentPlanSummary()
                        : null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Ownership Transfer
            |--------------------------------------------------------------------------
            */

            'ownership' => [

                'ownership_transferred' =>

                    (bool) $this->ownership_transferred,

                'title_deed_issued' =>

                    (bool) $this->title_deed_issued,

                'title_deed_number' =>

                    $this->title_deed_number,

                'pending_steps' =>

                    $this->ownershipPendingSteps(),

                'approvals' =>

                    $this->when(
                        $this->relationLoaded('ownershipApprovals'),
                        fn () => SaleOwnershipApprovalResource::collection(
                            $this->ownershipApprovals,
                        ),
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Brokerage
            |--------------------------------------------------------------------------
            */

            'brokerage' => [

                'broker_name' =>

                    $this->broker_name,

                'broker_commission' =>

                    $this->broker_commission,
            ],

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'documents' => [

                'contract_file_path' =>

                    $this->agreement
                        ?->contract_file_path,

                'completion_certificate_path' =>

                    $this->completion_certificate_path,

                'ownership_transfer_certificate_path' =>

                    $this->ownership_transfer_certificate_path,

                'has_completion_certificate' =>

                    ! empty($this->completion_certificate_path),

                'has_ownership_transfer_certificate' =>

                    ! empty($this->ownership_transfer_certificate_path),
            ],

            'payments' =>

                $this->when(
                    $this->relationLoaded('paymentAllocations'),
                    fn () => $this->paymentAllocations
                        ->map(fn ($allocation) => [
                            'id' => $allocation->payment_id,
                            'receipt_number' => $allocation->payment?->receipt_number,
                            'amount' => $allocation->amount_allocated,
                            'payment_date' => optional($allocation->payment?->payment_date)->format('Y-m-d'),
                            'payment_method' => $allocation->payment?->payment_method,
                        ])
                        ->values(),
                ),

            /*
            |--------------------------------------------------------------------------
            | Notes & Terms
            |--------------------------------------------------------------------------
            */

            'notes' => [

                'agreement_notes' =>

                    $this->agreement
                        ?->notes,

                'special_terms' =>

                    $this->special_terms,
            ],

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            'audit' => [

                'created_by' =>

                    $this->agreement
                        ?->created_by,

                'updated_by' =>

                    $this->agreement
                        ?->updated_by,

                'approved_by' =>

                    $this->agreement
                        ?->approved_by,

                'created_at' =>

                    optional(

                        $this->created_at

                    )->toDateTimeString(),

                'updated_at' =>

                    optional(

                        $this->updated_at

                    )->toDateTimeString(),
            ],

            'controls' => [

                'can_edit' =>

                    (bool) $this->agreement
                        ?->canBeEdited(),

                'can_execute' =>

                    $this->agreement
                        ?->status === 'draft',

                'can_cancel' =>

                    $this->agreement
                        ?->status === 'draft',

                'can_delete' =>

                    $this->agreement
                        ?->status === 'draft',

                'can_record_payment' =>

                    $this->agreement?->status === 'active'
                    && $this->balanceDue() > 0.009,

                'can_download_completion_certificate' =>

                    $this->agreement?->status === 'completed'
                    && ! empty($this->completion_certificate_path),

                'can_approve_ownership' =>

                    $this->agreement?->status === 'completed'
                    && ! $this->ownership_transferred
                    && count($this->ownershipPendingSteps()) > 0,

                'can_approve_legal' =>

                    $this->canApproveOwnershipStep(SaleOwnershipApproval::STEP_LEGAL),

                'can_approve_finance' =>

                    $this->canApproveOwnershipStep(SaleOwnershipApproval::STEP_FINANCE),

                'can_approve_manager' =>

                    $this->canApproveOwnershipStep(SaleOwnershipApproval::STEP_MANAGER),

                'can_issue_title_deed' =>

                    $this->agreement?->status === 'completed'
                    && $this->ownership_transferred
                    && ! $this->title_deed_issued,

                'can_download_ownership_transfer_certificate' =>

                    $this->agreement?->status === 'completed'
                    && $this->ownership_transferred
                    && ! empty($this->ownership_transfer_certificate_path),

                'can_download_sales_contract' =>

                    in_array($this->agreement?->status, ['active', 'completed'], true),

                'can_download_payment_plan_statement' =>

                    $this->isPaymentPlan(),

                'can_download_installment_schedule' =>

                    $this->isPaymentPlan(),
            ],
        ];
    }
}