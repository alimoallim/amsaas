<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Building $building): bool
    {
        return $this->belongsToCompany($user, $building);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, Building $building): bool
    {
        return $this->belongsToCompany($user, $building);
    }

    public function delete(User $user, Building $building): bool
    {
        return $this->belongsToCompany($user, $building);
    }

    protected function belongsToCompany(User $user, Building $building): bool
    {
        return (string) $user->company_id === (string) $building->company_id;
    }
}
