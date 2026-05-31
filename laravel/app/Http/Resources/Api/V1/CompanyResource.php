<?php

namespace App\Http\Resources\Api\V1;



use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
                'website' => $this->website,
            ],
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'country' => $this->country,
            ],
            'registration_number' => $this->registration_number,
            'tax_number' => $this->tax_number,
            'logo_url' => $this->logo_url,
            'status' => [
                'is_active' => $this->is_active,
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}
