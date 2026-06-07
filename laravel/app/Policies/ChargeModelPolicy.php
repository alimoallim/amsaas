<?php

namespace App\Policies;

use App\Models\ChargeModel;
use App\Models\User;

class ChargeModelPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, ChargeModel $chargeModel): bool
    {
        return $user->company_id === $chargeModel->company_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, ChargeModel $chargeModel): bool
    {
        return $user->company_id === $chargeModel->company_id;
    }

    public function delete(User $user, ChargeModel $chargeModel): bool
    {
        return $user->company_id === $chargeModel->company_id;
    }
}
