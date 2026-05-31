<?php
namespace App\Models\Scopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the tenant isolation scope.
     *
     * Every tenant-owned query automatically
     * filters records by authenticated user's
     * company_id.
     *
     * Example:
     *
     * Building::all()
     *
     * Automatically becomes:
     *
     * SELECT *
     * FROM buildings
     * WHERE company_id = auth()->user()->company_id
     */
    public function apply(
        Builder $builder,
        Model $model
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Skip scope for:
        | - Console commands
        | - Database seeders
        | - Queue workers without auth context
        | - Unauthenticated requests
        |--------------------------------------------------------------------------
        */

        if (
            app()->runningInConsole() ||
            !auth()->check()
        ) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get authenticated user
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Safety check
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | System Admin Bypass
        |--------------------------------------------------------------------------
        |
        | SYSTEM_ADMIN users can access
        | all tenant records globally.
        |
        | Future enterprise expansion:
        | - support platform analytics
        | - support support-engineer tooling
        | - support global administration
        |--------------------------------------------------------------------------
        */

        if (
            isset($user->role) &&
            $user->role === 'SYSTEM_ADMIN'
        ) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Enforce company isolation
        |--------------------------------------------------------------------------
        */

        if (
            empty($user->company_id)
        ) {

            abort(
                403,
                'Tenant context missing.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Apply WHERE company_id = ?
        |--------------------------------------------------------------------------
        */

        $builder->where(

            $model->getTable() . '.company_id',

            $user->company_id
        );
    }
}