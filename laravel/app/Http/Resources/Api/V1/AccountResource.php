<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'audit' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
                'created_by' => $this->created_by,
                'updated_by' => $this->updated_by,
            ],
            'controls' => [
                'can_edit' => true,
                'can_delete' => ! $this->is_system,
                'can_change_code' => ! $this->is_system,
                'can_change_type' => ! $this->is_system,
            ],
        ];
    }
}
