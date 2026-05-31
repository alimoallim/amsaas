<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;

trait BelongsToCompany
{
    /**
     * Boot tenant trait.
     */
    protected static function bootBelongsToCompany(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Apply Global Tenant Scope
        |--------------------------------------------------------------------------
        */

        static::addGlobalScope(
            CompanyScope::class,
            new CompanyScope()
        );

        /*
        |--------------------------------------------------------------------------
        | Automatically inject company_id
        |--------------------------------------------------------------------------
        */

        static::creating(function ($model) {

            /*
            |--------------------------------------------------------------------------
            | Skip CLI / queue contexts
            |--------------------------------------------------------------------------
            */

            if (
                app()->runningInConsole()
            ) {

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Require authenticated tenant
            |--------------------------------------------------------------------------
            */

            if (!auth()->check()) {

    return;
}

            /*
            |--------------------------------------------------------------------------
            | Prevent frontend company spoofing
            |--------------------------------------------------------------------------
            */

            if (
                empty($model->company_id)
            ) {

                $model->company_id =

                    auth()->user()->company_id;
            }
        });
    }

    /**
     * Company relationship.
     */
    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }
}