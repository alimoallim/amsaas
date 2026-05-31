<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ApartmentResource extends JsonResource
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
            | Core Identity
            |--------------------------------------------------------------------------
            */

            'id' =>

                $this->id,

            'company_id' =>

                $this->company_id,

            /*
            |--------------------------------------------------------------------------
            | Building
            |--------------------------------------------------------------------------
            */

            'building' =>

                $this->whenLoaded(

                    'building',

                    fn () => [

                        'id' =>

                            $this->building->id,

                        'name' =>

                            $this->building->name,

                        'city' =>

                            $this->building->city,

                        'country' =>

                            $this->building->country,

                        'currency_code' =>

                            $this->building->currency_code,
                    ]
                ),

            /*
            |--------------------------------------------------------------------------
            | Unit Information
            |--------------------------------------------------------------------------
            */

            'unit' => [

                'unit_number' =>

                    $this->unit_number,

                'floor' =>

                    $this->floor,

                'property_type' =>

                    $this->property_type,
            ],

            /*
            |--------------------------------------------------------------------------
            | Layout
            |--------------------------------------------------------------------------
            */

            'layout' => [

                'bedrooms' =>

                    $this->bedrooms,

                'bathrooms' =>

                    $this->bathrooms,

                'area_sqm' =>

                    $this->area_sqm,
            ],

            /*
            |--------------------------------------------------------------------------
            | Listing
            |--------------------------------------------------------------------------
            */

            'listing' => [

                'listing_type' =>

                    $this->listing_type,

                'inventory_status' =>

                    $this->inventory_status,

                'is_available' =>

                    $this->is_available,

                'can_be_rented' =>

                    $this->canBeRented(),

                'can_be_sold' =>

                    $this->canBeSold(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'pricing' => [

                'market_rent_price' =>

                    $this->market_rent_price,

                'market_sale_price' =>

                    $this->market_sale_price,

                'security_deposit' =>

                    $this->security_deposit,

                'currency' =>

                    $this->currency,

                'effective_price' =>

                    $this->effective_price,
            ],

            /*
            |--------------------------------------------------------------------------
            | Features
            |--------------------------------------------------------------------------
            */

            'features' => [

                'has_balcony' =>

                    $this->has_balcony,

                'has_parking' =>

                    $this->has_parking,

                'has_storage' =>

                    $this->has_storage,

                'is_furnished' =>

                    $this->is_furnished,
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>

                $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'audit' => [

                'created_by' =>

                    $this->created_by,

                'updated_by' =>

                    $this->updated_by,
            ],

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'timestamps' => [

                'created_at' =>

                    optional(
                        $this->created_at
                    )->toIso8601String(),

                'updated_at' =>

                    optional(
                        $this->updated_at
                    )->toIso8601String(),

                'deleted_at' =>

                    optional(
                        $this->deleted_at
                    )->toIso8601String(),
            ],
        ];
    }
}