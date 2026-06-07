<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        return [

            'id' =>

                $this->id,

            'company_id' =>

                $this->company_id,

            'tenant_code' =>

                $this->tenant_code,

            'tenant_type' =>

                $this->tenant_type,

            'display_name' => $this->full_display_name ?: null,

            'full_display_name' => $this->full_display_name ?: null,

            'name' => [

                'first_name' =>

                    $this->first_name,

                'middle_name' =>

                    $this->middle_name,

                'last_name' =>

                    $this->last_name,
            ],

            'company_name' =>

                $this->company_name,

            'contact' => [

                'email' =>

                    $this->email,

                'phone' =>

                    $this->phone,

                'alternate_phone' =>

                    $this->alternate_phone,
            ],

            'identity' => [

                'national_id' =>

                    $this->national_id,

                'passport_number' =>

                    $this->passport_number,

                'tax_number' =>

                    $this->tax_number,

                'nationality' =>

                    $this->nationality,

                'date_of_birth' =>

                    $this->date_of_birth
                        ?->format('Y-m-d'),

                'gender' =>

                    $this->gender,

                'occupation' =>

                    $this->occupation,
            ],

            'address' => [

                'country' =>

                    $this->country,

                'city' =>

                    $this->city,

                'address' =>

                    $this->address,

                'postal_code' =>

                    $this->postal_code,
            ],

            'emergency_contact' => [

                'name' =>

                    $this->emergency_contact_name,

                'phone' =>

                    $this->emergency_contact_phone,

                'relationship' =>

                    $this->emergency_contact_relationship,
            ],

            'status' => [

                'value' =>

                    $this->status,
            ],

            'notes' =>

                $this->notes,

            'audit' => [

                'created_by' =>

                    $this->created_by,

                'updated_by' =>

                    $this->updated_by,
            ],

            'timestamps' => [

                'created_at' =>

                    $this->created_at
                        ?->toIso8601String(),

                'updated_at' =>

                    $this->updated_at
                        ?->toIso8601String(),
            ],
        ];
    }
}