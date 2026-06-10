<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SaleOwnershipApproval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SaleOwnershipApproval */
class SaleOwnershipApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step' => $this->step,
            'step_label' => SaleOwnershipApproval::stepLabel($this->step),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'notes' => $this->notes,
            'approved_by' => $this->when(
                $this->relationLoaded('approvedBy') && $this->approvedBy,
                fn () => [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ],
            ),
        ];
    }
}
