<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Company;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Meter;
use App\Policies\AccountPolicy;
use App\Policies\ChargeModelPolicy;
use App\Policies\ChargePolicy;
use App\Policies\ChargeTypePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\MeterPolicy;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\MultiTenancy\TenancyManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenancyManager::class, function ($app) {
        return new \App\Services\MultiTenancy\TenancyManager();
    });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(ChargeType::class, ChargeTypePolicy::class);

        Company::created(function (Company $company): void {
            app(ChartOfAccountsService::class)->seedDefaults($company);
        });
        Gate::policy(ChargeModel::class, ChargeModelPolicy::class);
        Gate::policy(Charge::class, ChargePolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Meter::class, MeterPolicy::class);
    }
}
