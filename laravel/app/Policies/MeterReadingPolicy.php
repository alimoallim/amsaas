<?php

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;

class MeterReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, MeterReading $meterReading): bool
    {
        return $this->belongsToCompany($user, $meterReading);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, MeterReading $meterReading): bool
    {
        return $this->belongsToCompany($user, $meterReading);
    }

    public function approve(User $user, MeterReading $meterReading): bool
    {
        return $this->belongsToCompany($user, $meterReading);
    }

    public function reject(User $user, MeterReading $meterReading): bool
    {
        return $this->belongsToCompany($user, $meterReading);
    }

    public function bulkManage(User $user): bool
    {
        return ! empty($user->company_id);
    }

    protected function belongsToCompany(User $user, MeterReading $meterReading): bool
    {
        return (string) $user->company_id === (string) $meterReading->company_id;
    }
}
