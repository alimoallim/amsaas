<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ChargeModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalAgreementResource extends JsonResource
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

                    (bool) $this->agreement
                        ?->is_active,

                'is_draft' =>

                    (bool) $this->agreement
                        ?->is_draft,

                'is_expired' =>

                    (bool) $this->agreement
                        ?->is_expired,
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

                        /*
                        |--------------------------------------------------------------------------
                        | Unit Details
                        |--------------------------------------------------------------------------
                        */

                        'unit_number' =>

                            $this->agreement
                                ?->apartment
                                ?->unit
                                ?->unit_number

                            ??

                            $this->agreement
                                ?->apartment
                                ?->unit_number,

                        'floor' =>

                            $this->agreement
                                ?->apartment
                                ?->unit
                                ?->floor

                            ??

                            $this->agreement
                                ?->apartment
                                ?->floor,

                        /*
                        |--------------------------------------------------------------------------
                        | Building
                        |--------------------------------------------------------------------------
                        */

                        'building' => [

                            'id' =>

                                $this->agreement
                                    ?->apartment
                                    ?->building
                                    ?->id,

                            'name' =>

                                $this->agreement
                                    ?->apartment
                                    ?->building
                                    ?->name,

                            'city' =>

                                $this->agreement
                                    ?->apartment
                                    ?->building
                                    ?->city,
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | Listing / Inventory
                        |--------------------------------------------------------------------------
                        */

                        'listing_type' =>

                            $this->agreement
                                ?->apartment
                                ?->listing
                                ?->listing_type

                            ??

                            $this->agreement
                                ?->apartment
                                ?->listing_type,

                        'inventory_status' =>

                            $this->agreement
                                ?->apartment
                                ?->listing
                                ?->inventory_status

                            ??

                            $this->agreement
                                ?->apartment
                                ?->inventory_status,

                        'is_available' =>

                            (bool)

                            (

                                $this->agreement
                                    ?->apartment
                                    ?->listing
                                    ?->is_available

                                ??

                                false
                            ),

                        /*
                        |--------------------------------------------------------------------------
                        | Layout
                        |--------------------------------------------------------------------------
                        */

                        'bedrooms' =>

                            $this->agreement
                                ?->apartment
                                ?->layout
                                ?->bedrooms

                            ??

                            $this->agreement
                                ?->apartment
                                ?->bedrooms,

                        'bathrooms' =>

                            $this->agreement
                                ?->apartment
                                ?->layout
                                ?->bathrooms

                            ??

                            $this->agreement
                                ?->apartment
                                ?->bathrooms,

                        'area_sqm' =>

                            $this->agreement
                                ?->apartment
                                ?->layout
                                ?->area_sqm

                            ??

                            $this->agreement
                                ?->apartment
                                ?->area_sqm,
                    ]

                ),

            /*
            |--------------------------------------------------------------------------
            | Tenant Information
            |--------------------------------------------------------------------------
            */

            'tenant' =>

                $this->when(

                    $this->agreement
                    ?->tenant,

                    fn () => [

                        'id' =>

                            $this->agreement
                                ?->tenant
                                ?->id,

                        'tenant_code' =>

                            $this->agreement
                                ?->tenant
                                ?->tenant_code,

                        'display_name' =>

                            $this->agreement
                                ?->tenant
                                ?->full_display_name ?: null,

                        'email' =>

                            $this->agreement
                                ?->tenant
                                ?->email,

                        'phone' =>

                            $this->agreement
                                ?->tenant
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

                'terminated_at' =>

                    optional(

                        $this->agreement
                            ?->terminated_at

                    )->toDateTimeString(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Financial Terms
            |--------------------------------------------------------------------------
            */

            'financials' => [

                'monthly_rent' =>

                    $this->monthly_rent,

                'security_deposit' =>

                    $this->security_deposit,

                'contract_amount' =>

                    $this->agreement
                        ?->contract_amount,

                'currency' =>

                    $this->agreement
                        ?->currency,

                'payment_due_day' =>

                    $this->payment_due_day,

                'billing_cycle' =>

                    $this->billing_cycle,

                'late_fee_amount' =>

                    $this->late_fee_amount,

                'grace_period_days' =>

                    $this->grace_period_days,
            ],

            /*
            |--------------------------------------------------------------------------
            | Utilities Coverage
            |--------------------------------------------------------------------------
            */

            'utilities' => [

                'includes_water' =>

                    (bool) $this->includes_water,

                'includes_electricity' =>

                    (bool) $this->includes_electricity,

                'includes_internet' =>

                    (bool) $this->includes_internet,
            ],

            /*
            |--------------------------------------------------------------------------
            | Recurring billing (agreement charges)
            |--------------------------------------------------------------------------
            */

            'billing' => $this->when(
                $this->includeDetailPayload($request),
                fn () => $this->billingPayload(),
            ),

            'utility_usage' => $this->when(
                $this->includeDetailPayload($request),
                fn () => $this->utilityUsagePayload(),
            ),

            /*
            |--------------------------------------------------------------------------
            | Renewal Policy
            |--------------------------------------------------------------------------
            */

            'renewal' => [

                'auto_renew' =>

                    (bool) $this->auto_renew,

                'renewal_notice_days' =>

                    $this->renewal_notice_days,
            ],

            /*
            |--------------------------------------------------------------------------
            | Termination
            |--------------------------------------------------------------------------
            */

            'termination' => [

                'terminated_by' =>

                    $this->agreement
                        ?->terminated_by,

                'termination_reason' =>

                    $this->agreement
                        ?->termination_reason,
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
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
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

                'created_at' =>

                    optional(
                        $this->created_at
                    )->toDateTimeString(),

                'updated_at' =>

                    optional(
                        $this->updated_at
                    )->toDateTimeString(),
            ],

            'controls' => $this->agreementControls(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function agreementControls(): array
    {
        $agreement = $this->agreement;
        $status = $agreement?->status;

        $finalized = in_array($status, [
            \App\Models\Agreement::STATUS_TERMINATED,
            \App\Models\Agreement::STATUS_COMPLETED,
            \App\Models\Agreement::STATUS_CANCELLED,
        ], true);

        $machine = app(\App\Services\Agreements\AgreementStateMachine::class);

        return [
            'can_edit' => $agreement && ! $finalized,
            'can_delete' => $status === \App\Models\Agreement::STATUS_DRAFT,
            'can_approve' => $agreement
                && $machine->canTransition($agreement, \App\Models\Agreement::STATUS_APPROVED),
            'can_activate' => $agreement
                && $status !== \App\Models\Agreement::STATUS_ACTIVE
                && ($agreement->canBeActivated() ?? false)
                && $machine->canTransition($agreement, \App\Models\Agreement::STATUS_ACTIVE),
            'can_terminate' => $status === \App\Models\Agreement::STATUS_ACTIVE,
        ];
    }

    protected function includeDetailPayload(Request $request): bool
    {
        return $request->routeIs('rental-agreements.show');
    }

    protected function utilityUsagePayload(): array
    {
        return app(\App\Services\Billing\AgreementUtilityUsageService::class)
            ->summarize($this->resource);
    }

    protected function billingPayload(): array
    {
        $charges = $this->agreement?->relationLoaded('agreementCharges')
            ? $this->agreement->agreementCharges
            : collect();

        $rentCharge = $charges->first(function ($charge) {
            $strategy = $charge->chargeModel?->pricing_strategy;

            return $strategy === ChargeModel::STRATEGY_AGREEMENT_RENT;
        });

        $recurring = $charges
            ->filter(function ($charge) use ($rentCharge) {
                if ($rentCharge && $charge->id === $rentCharge->id) {
                    return false;
                }

                $strategy = $charge->chargeModel?->pricing_strategy;

                return $strategy !== ChargeModel::STRATEGY_AGREEMENT_RENT;
            })
            ->values();

        return [
            'rent_charge_model_id' => $rentCharge?->charge_model_id,
            'rent_charge' => $rentCharge
                ? new AgreementChargeResource($rentCharge)
                : null,
            'recurring_charges' => AgreementChargeResource::collection($recurring),
        ];
    }
}