<?php

namespace App\Policies;

use App\Models\SaleAgreement;
use App\Models\User;

class SaleAgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, SaleAgreement $saleAgreement): bool
    {
        return $this->belongsToCompany($user, $saleAgreement);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, SaleAgreement $saleAgreement): bool
    {
        return $this->belongsToCompany($user, $saleAgreement);
    }

    public function delete(User $user, SaleAgreement $saleAgreement): bool
    {
        return $this->belongsToCompany($user, $saleAgreement);
    }

    protected function belongsToCompany(User $user, SaleAgreement $saleAgreement): bool
    {
        $saleAgreement->loadMissing('agreement');

        return $saleAgreement->agreement !== null
            && (string) $user->company_id === (string) $saleAgreement->agreement->company_id;
    }
}
