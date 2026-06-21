<?php

namespace App\Policies;

use App\Models\SaleReservation;
use App\Models\User;

class SaleReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, SaleReservation $reservation): bool
    {
        return $this->belongsToCompany($user, $reservation);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, SaleReservation $reservation): bool
    {
        return $this->belongsToCompany($user, $reservation);
    }

    public function delete(User $user, SaleReservation $reservation): bool
    {
        return $this->belongsToCompany($user, $reservation);
    }

    public function recordDeposit(User $user, SaleReservation $reservation): bool
    {
        return $this->belongsToCompany($user, $reservation);
    }

    protected function belongsToCompany(User $user, SaleReservation $reservation): bool
    {
        return (string) $user->company_id === (string) $reservation->company_id;
    }
}
