<?php

namespace App\Policies;

use App\Models\MonthlyInvoice;
use App\Models\User;

class MonthlyInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function view(User $user, MonthlyInvoice $invoice): bool
    {
        return $this->belongsToCompany($user, $invoice);
    }

    public function create(User $user): bool
    {
        return ! empty($user->company_id);
    }

    public function update(User $user, MonthlyInvoice $invoice): bool
    {
        return $this->belongsToCompany($user, $invoice);
    }

    public function delete(User $user, MonthlyInvoice $invoice): bool
    {
        return $this->belongsToCompany($user, $invoice);
    }

    public function finalize(User $user, MonthlyInvoice $invoice): bool
    {
        return $this->belongsToCompany($user, $invoice);
    }

    public function void(User $user, MonthlyInvoice $invoice): bool
    {
        return $this->belongsToCompany($user, $invoice);
    }

    public function creditNote(User $user, MonthlyInvoice $invoice): bool
    {
        return $this->belongsToCompany($user, $invoice);
    }

    public function bulkManage(User $user): bool
    {
        return ! empty($user->company_id);
    }

    protected function belongsToCompany(User $user, MonthlyInvoice $invoice): bool
    {
        return (string) $user->company_id === (string) $invoice->company_id;
    }
}
