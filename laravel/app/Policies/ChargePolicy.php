<?php

namespace App\Policies;

use App\Models\Charge;
use App\Models\User;

class ChargePolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Charge $charge): bool
    {
        return $user->company_id === $charge->company_id;
    }

    public function approve(User $user, Charge $charge): bool
    {
        return $user->company_id === $charge->company_id;
    }

    public function reject(User $user, Charge $charge): bool
    {
        return $user->company_id === $charge->company_id;
    }
}
