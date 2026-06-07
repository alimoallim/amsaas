<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->company_id === $company->id;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->company_id === $company->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->company_id === $company->id;
    }
}
