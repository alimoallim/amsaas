<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Agreement;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $inventoryStatus = $this->inventory_status;
        $activeLease = $this->relationLoaded('activeLease') ? $this->activeLease : null;

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,

            'building' => $this->whenLoaded(
                'building',
                fn () => [
                    'id' => $this->building->id,
                    'name' => $this->building->name,
                    'city' => $this->building->city,
                    'country' => $this->building->country,
                ]
            ),

            'unit' => [
                'unit_number' => $this->unit_number,
                'floor' => $this->floor,
                'property_type' => $this->property_type,
            ],

            'layout' => [
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'area_sqm' => $this->area_sqm,
            ],

            'listing' => [
                'listing_type' => $this->listing_type,
                'inventory_status' => $inventoryStatus,
                'is_available' => $this->is_available,
                'can_be_rented' => $this->canBeRented(),
                'can_be_sold' => $this->canBeSold(),
            ],

            'occupancy' => [
                'inventory_status' => $inventoryStatus,
                'inventory_label' => str($inventoryStatus)->replace('_', ' ')->title()->toString(),
                'has_active_lease' => (bool) $activeLease,
                'active_agreement_number' => $activeLease?->agreement_number,
                'hint' => $this->occupancyHint($inventoryStatus, $activeLease),
            ],

            'pricing' => [
                'market_rent_price' => $this->market_rent_price,
                'market_sale_price' => $this->market_sale_price,
                'security_deposit' => $this->security_deposit,
                'currency' => $this->currency,
                'effective_price' => $this->effective_price,
            ],

            'features' => [
                'has_balcony' => $this->has_balcony,
                'has_parking' => $this->has_parking,
                'has_storage' => $this->has_storage,
                'is_furnished' => $this->is_furnished,
            ],

            'notes' => $this->notes,

            'audit' => [
                'created_by' => $this->created_by,
                'updated_by' => $this->updated_by,
            ],

            'timestamps' => [
                'created_at' => optional($this->created_at)->toIso8601String(),
                'updated_at' => optional($this->updated_at)->toIso8601String(),
                'deleted_at' => optional($this->deleted_at)->toIso8601String(),
            ],

            'controls' => [
                'can_edit' => true,
                'can_delete' => ($this->blocking_leases_count ?? 0) === 0,
            ],
        ];
    }

    private function occupancyHint(string $inventoryStatus, ?Agreement $activeLease): string
    {
        if ($activeLease) {
            return 'Active lease '.$activeLease->agreement_number;
        }

        return match ($inventoryStatus) {
            Apartment::STATUS_AVAILABLE => 'Available for lease',
            Apartment::STATUS_RESERVED => 'Reserved — draft agreement',
            Apartment::STATUS_OCCUPIED => 'Marked occupied (no active lease record)',
            Apartment::STATUS_MAINTENANCE => 'Under maintenance',
            Apartment::STATUS_UNDER_CONTRACT => 'Under contract',
            default => str($inventoryStatus)->replace('_', ' ')->title()->toString(),
        };
    }
}
