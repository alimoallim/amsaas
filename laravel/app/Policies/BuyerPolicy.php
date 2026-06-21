<?php

namespace App\Policies;

use App\Models\Buyer;
use App\Models\User;

class BuyerPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Buyer $buyer): bool
    {
        return $this->belongsToCompany($user, $buyer);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, Buyer $buyer): bool
    {
        return $this->belongsToCompany($user, $buyer);
    }

    public function delete(User $user, Buyer $buyer): bool
    {
        return $this->belongsToCompany($user, $buyer);
    }

    protected function belongsToCompany(User $user, Buyer $buyer): bool
    {
        return (string) $user->company_id === (string) $buyer->company_id;
    }
}
