<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Account $account): bool
    {
        return $user->company_id === $account->company_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, Account $account): bool
    {
        return $user->company_id === $account->company_id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->company_id === $account->company_id;
    }
}
