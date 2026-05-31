<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\V1\SystemController;

use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ApartmentController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\BuyerController;
use App\Http\Controllers\Api\V1\RentalAgreementController;
use App\Http\Controllers\Api\V1\SaleAgreementController;
use App\Http\Controllers\Api\V1\UtilityReadingController;
use App\Http\Controllers\Api\V1\MonthlyInvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
 use App\Http\Controllers\Api\V1\MeterController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\SetupController;
use App\Http\Controllers\Api\V1\MeterReadingController;

/*
|--------------------------------------------------------------------------
| API V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SaaS Bootstrap Status
    |--------------------------------------------------------------------------
    */

    Route::get(

        'system/bootstrap-status',

        [SystemController::class, 'bootstrapStatus']
    );

    /*
    |--------------------------------------------------------------------------
    | Initial Company Setup
    |--------------------------------------------------------------------------
    */

    Route::post(

        'setup/company',

        [SetupController::class, 'registerCompany']
    );

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post(

        'login',

        [AuthController::class, 'login']
    );

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Authenticated User
            |--------------------------------------------------------------------------
            */

            Route::get(

                'me',

                [AuthController::class, 'me']
            );

            Route::post(

                'logout',

                [AuthController::class, 'logout']
            );

            /*
            |--------------------------------------------------------------------------
            | Companies
            |--------------------------------------------------------------------------
            */

            Route::apiResource(

                'companies',

                CompanyController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Buildings
            |--------------------------------------------------------------------------
            */

            Route::apiResource(

                'buildings',

                BuildingController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Apartments
            |--------------------------------------------------------------------------
            */

            Route::get(

                'apartments/summary',

                [ApartmentController::class, 'summary']
            );

            Route::apiResource(

                'apartments',

                ApartmentController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Stakeholders
            |--------------------------------------------------------------------------
            */

            Route::apiResource(

                'tenants',

                TenantController::class
            );

            Route::apiResource(

                'buyers',

                BuyerController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Agreements
            |--------------------------------------------------------------------------
            */

            Route::apiResource(

                'rental-agreements',

                RentalAgreementController::class
            );

            Route::apiResource(

                'sale-agreements',

                SaleAgreementController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Utility Readings
            |--------------------------------------------------------------------------
            */

            Route::apiResource(

                'utility-readings',

                UtilityReadingController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Invoices
            |--------------------------------------------------------------------------
            */

            Route::post(

                'invoices/{invoice}/finalize',

                [MonthlyInvoiceController::class, 'finalize']
            );

            Route::apiResource(

                'invoices',

                MonthlyInvoiceController::class
            );

            /*
            |--------------------------------------------------------------------------
            | Payments
            |--------------------------------------------------------------------------
            */

          



        /*
        |--------------------------------------------------------------------------
        | Meter Management
        |--------------------------------------------------------------------------
        */

        Route::apiResource(

            'meters',

            MeterController::class
        );

        /*
        |--------------------------------------------------------------------------
        | Lifecycle Operations
        |--------------------------------------------------------------------------
        */

        Route::prefix('meters/{meter}')

            ->group(function () {

                Route::post(

                    'activate',

                    [
                        MeterController::class,

                        'activate',
                    ]
                );

                Route::post(

                    'faulty',

                    [
                        MeterController::class,

                        'markFaulty',
                    ]
                );

                Route::post(

                    'maintenance',

                    [
                        MeterController::class,

                        'maintenance',
                    ]
                );

                Route::post(

                    'maintenance/complete',

                    [
                        MeterController::class,

                        'completeMaintenance',
                    ]
                );

                Route::post(

                    'decommission',

                    [
                        MeterController::class,

                        'decommission',
                    ]
                );

                Route::post(

                    'inspection/complete',

                    [
                        MeterController::class,

                        'completeInspection',
                    ]
                );
            });
    
    /*
|--------------------------------------------------------------------------
| Meter Readings
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'meter-readings',
    MeterReadingController::class
);

Route::get(
    'meter-readings/anomalies',
    [
        MeterReadingController::class,
        'anomalies'
    ]
);

Route::post(
    'meter-readings/{meterReading}/approve',
    [
        MeterReadingController::class,
        'approve'
    ]
);

Route::post(
    'meter-readings/{meterReading}/reject',
    [
        MeterReadingController::class,
        'reject'
    ]
);
        });
});