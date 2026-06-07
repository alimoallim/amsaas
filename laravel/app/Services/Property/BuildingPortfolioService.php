<?php

namespace App\Services\Property;

use App\Exceptions\BusinessRuleException;
use App\Models\Apartment;
use App\Models\Building;

class BuildingPortfolioService
{
    public function syncUnitCount(Building $building): void
    {
        $count = Apartment::query()
            ->where('building_id', $building->id)
            ->count();

        $building->update([
            'total_units' => $count,
        ]);
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertCanDelete(Building $building): void
    {
        $hasUnits = Apartment::query()
            ->where('building_id', $building->id)
            ->exists();

        if ($hasUnits) {
            throw new BusinessRuleException(
                'Remove or reassign all apartments before deleting this building.',
                'BUILDING_HAS_UNITS',
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertBuildingBelongsToCompany(
        string $buildingId,
        string $companyId,
    ): Building {
        $building = Building::query()
            ->where('id', $buildingId)
            ->where('company_id', $companyId)
            ->first();

        if (! $building) {
            throw new BusinessRuleException(
                'Building not found for your organization.',
                'BUILDING_NOT_FOUND',
            );
        }

        if (! $building->is_active) {
            throw new BusinessRuleException(
                'Cannot assign units to an inactive building.',
                'BUILDING_INACTIVE',
            );
        }

        return $building;
    }
}
