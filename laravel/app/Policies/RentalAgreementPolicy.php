<?php

namespace App\Policies;

use App\Models\RentalAgreement;
use App\Models\User;

class RentalAgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, RentalAgreement $rentalAgreement): bool
    {
        return $this->belongsToCompany($user, $rentalAgreement);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, RentalAgreement $rentalAgreement): bool
    {
        return $this->belongsToCompany($user, $rentalAgreement);
    }

    public function delete(User $user, RentalAgreement $rentalAgreement): bool
    {
        return $this->belongsToCompany($user, $rentalAgreement);
    }

    public function consolidateBilling(User $user, RentalAgreement $rentalAgreement): bool
    {
        return $this->belongsToCompany($user, $rentalAgreement);
    }

    public function applyDeposit(User $user, RentalAgreement $rentalAgreement): bool
    {
        return $this->belongsToCompany($user, $rentalAgreement);
    }

    protected function belongsToCompany(User $user, RentalAgreement $rentalAgreement): bool
    {
        $rentalAgreement->loadMissing('agreement');

        return $rentalAgreement->agreement !== null
            && (string) $user->company_id === (string) $rentalAgreement->agreement->company_id;
    }
}
