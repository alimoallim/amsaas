<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the tenant isolation scope.
     *
     * Filters by auth()->user()->company_id for HTTP and feature tests.
     * Skipped in console (migrations, seeders, artisan) except PHPUnit (runningUnitTests).
     */
    public function apply(Builder $builder, Model $model): void
    {
        /*
        |--------------------------------------------------------------------------
        | Skip for console / queue without HTTP auth (not PHPUnit feature tests)
        |--------------------------------------------------------------------------
        */
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            $companyId = TenantContext::currentCompanyId();

            if ($companyId) {
                $builder->where(
                    $model->getTable().'.company_id',
                    $companyId
                );
            }

            return;
        }

        if (! auth()->check()) {
            $companyId = TenantContext::currentCompanyId();

            if ($companyId) {
                $builder->where(
                    $model->getTable().'.company_id',
                    $companyId
                );
            }

            return;
        }

        $user = auth()->user();

        if (! $user) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | System Admin Bypass
        |--------------------------------------------------------------------------
        */
        if (
            isset($user->role) &&
            $user->role === 'SYSTEM_ADMIN'
        ) {
            return;
        }

        if (empty($user->company_id)) {
            abort(403, 'Tenant context missing.');
        }

        $builder->where(
            $model->getTable().'.company_id',
            $user->company_id
        );
    }
}
