<?php



namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\ApartmentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        return [

            'id' =>

                $this->id,

            'company_id' =>

                $this->company_id,

            'name' =>

                $this->name,

            'code' =>

                $this->code,

            'type' =>

                $this->type,

            'address' =>

                $this->address,

            'city' =>

                $this->city,

            'country' =>

                $this->country,

            'timezone' =>

                $this->timezone,

            'operating_currency' =>

                $this->operating_currency,

            'total_floors' =>

                $this->total_floors,

            'total_units' =>

                $this->total_units,

            'description' =>

                $this->description,

            'is_active' =>

                (bool) $this->is_active,

            'created_at' =>

                $this->created_at,

            'updated_at' =>

                $this->updated_at,

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            'apartments' =>

                ApartmentResource::collection(
                    $this->whenLoaded(
                        'apartments'
                    )
                ),

            'apartments_count' => $this->whenCounted('apartments'),

            'controls' => [
                'can_edit' => true,
                'can_delete' => ($this->apartments_count ?? 0) === 0,
            ],
        ];
    }
}