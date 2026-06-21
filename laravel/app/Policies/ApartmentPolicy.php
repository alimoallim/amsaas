<?php

namespace App\Policies;

use App\Models\Apartment;
use App\Models\User;

class ApartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Apartment $apartment): bool
    {
        return $this->belongsToCompany($user, $apartment);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $this->belongsToCompany($user, $apartment);
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $this->belongsToCompany($user, $apartment);
    }

    protected function belongsToCompany(User $user, Apartment $apartment): bool
    {
        return (string) $user->company_id === (string) $apartment->company_id;
    }
}
