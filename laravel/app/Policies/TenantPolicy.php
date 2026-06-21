<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->belongsToCompany($user, $tenant);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->belongsToCompany($user, $tenant);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->belongsToCompany($user, $tenant);
    }

    protected function belongsToCompany(User $user, Tenant $tenant): bool
    {
        return (string) $user->company_id === (string) $tenant->company_id;
    }
}
