<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Meter;

class MeterPolicy
{
    public function viewAny(
        User $user
    ): bool {

        return true;
    }

    public function view(
        User $user,
        Meter $meter
    ): bool {

        return
            $user->company_id
            ===
            $meter->company_id;
    }

    public function update(
        User $user,
        Meter $meter
    ): bool {

        return
            $user->company_id
            ===
            $meter->company_id;
    }

    public function delete(
        User $user,
        Meter $meter
    ): bool {

        return
            $user->company_id
            ===
            $meter->company_id;
    }
}