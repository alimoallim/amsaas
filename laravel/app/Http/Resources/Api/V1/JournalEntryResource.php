<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_number' => $this->entry_number,
            'entry_date' => optional($this->entry_date)->format('Y-m-d'),
            'description' => $this->description,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'total_debit' => (float) $this->total_debit,
            'total_credit' => (float) $this->total_credit,
            'lines' => $this->whenLoaded('lines', function () {
                return $this->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'account_code' => $line->account?->code,
                    'account_name' => $line->account?->name,
                    'debit_amount' => (float) $line->debit_amount,
                    'credit_amount' => (float) $line->credit_amount,
                    'description' => $line->description,
                ]);
            }),
        ];
    }
}
