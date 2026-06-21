<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\Buyer;
use App\Models\RentalAgreement;
use App\Models\SaleAgreement;
use App\Models\SaleReservation;
use App\Models\Tenant;
use App\Policies\AccountPolicy;
use App\Policies\ApartmentPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\ChargeModelPolicy;
use App\Policies\ChargePolicy;
use App\Policies\ChargeTypePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\MeterPolicy;
use App\Policies\MeterReadingPolicy;
use App\Policies\MonthlyInvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\BuyerPolicy;
use App\Policies\RentalAgreementPolicy;
use App\Policies\SaleAgreementPolicy;
use App\Policies\SaleReservationPolicy;
use App\Policies\TenantPolicy;
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
        Gate::policy(MeterReading::class, MeterReadingPolicy::class);
        Gate::policy(MonthlyInvoice::class, MonthlyInvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(RentalAgreement::class, RentalAgreementPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Building::class, BuildingPolicy::class);
        Gate::policy(Apartment::class, ApartmentPolicy::class);
        Gate::policy(Buyer::class, BuyerPolicy::class);
        Gate::policy(SaleAgreement::class, SaleAgreementPolicy::class);
        Gate::policy(SaleReservation::class, SaleReservationPolicy::class);
    }
}
