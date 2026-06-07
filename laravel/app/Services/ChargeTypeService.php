<?php

namespace App\Services;

use App\Models\ChargeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChargeTypeService
{
    /**
     * Coordinate the database creation sequence for a multi-tenant ChargeType record.
     */
    public function create(array $data, string $companyId, string $userId): ChargeType
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $data['id'] = Str::uuid()->toString();
            $data['company_id'] = $companyId;
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            return ChargeType::create($data);
        });
    }

    /**
     * Update an isolated single target record.
     */
    public function update(ChargeType $chargeType, array $data, string $userId): ChargeType
    {
        return DB::transaction(function () use ($chargeType, $data, $userId) {
            $data['updated_by'] = $userId;
            $chargeType->update($data);
            
            return $chargeType;
        });
    }

    /**
     * Safely drop or archive a record via SoftDeletes.
     */
    public function delete(ChargeType $chargeType): bool
    {
        return DB::transaction(function () use ($chargeType) {
            return $chargeType->delete();
        });
    }
}