<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->belongsToCompany($user, $payment);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->belongsToCompany($user, $payment);
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $this->belongsToCompany($user, $payment);
    }

    protected function belongsToCompany(User $user, Payment $payment): bool
    {
        return (string) $user->company_id === (string) $payment->company_id;
    }
}
