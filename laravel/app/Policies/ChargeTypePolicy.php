<?php

namespace App\Policies;

use App\Models\ChargeType;
use App\Models\User;

class ChargeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, ChargeType $chargeType): bool
    {
        return $user->company_id === $chargeType->company_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, ChargeType $chargeType): bool
    {
        return $user->company_id === $chargeType->company_id;
    }

    public function delete(User $user, ChargeType $chargeType): bool
    {
        return $user->company_id === $chargeType->company_id;
    }
}
