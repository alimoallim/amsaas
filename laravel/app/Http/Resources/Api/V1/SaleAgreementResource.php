<?php

namespace App\Http\Resources\Api\V1;

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

            'customer' =>

                $this->when(

                    $this->agreement
                    ?->tenant,

                    fn () => [

                        'id' =>

                            $this->agreement
                                ?->tenant
                                ?->id,

                        'customer_code' =>

                            $this->agreement
                                ?->tenant
                                ?->tenant_code,

                        'display_name' =>

                            $this->agreement
                                ?->tenant
                                ?->full_display_name,

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

                'remaining_balance' =>

                    $this->remaining_balance,

                'contract_amount' =>

                    $this->agreement
                        ?->contract_amount,

                'currency' =>

                    $this->agreement
                        ?->currency,

                'broker_commission' =>

                    $this->broker_commission,
            ],

            /*
            |--------------------------------------------------------------------------
            | Installment Plan
            |--------------------------------------------------------------------------
            */

            'installments' => [

                'is_installment_sale' =>

                    (bool) $this->is_installment_sale,

                'installment_months' =>

                    $this->installment_months,

                'monthly_installment_amount' =>

                    $this->monthly_installment_amount,
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
            ],

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
        ];
    }
}