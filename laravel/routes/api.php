<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Controllers Import
|--------------------------------------------------------------------------
| All controllers are structured under the Version 1 (V1) namespace.
|
*/
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\ChargeTypeController; 
use App\Http\Controllers\Api\V1\ChargeController;
use App\Http\Controllers\Api\V1\ChargeModelController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ApartmentController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\BuyerController;
use App\Http\Controllers\Api\V1\RentalAgreementController;
use App\Http\Controllers\Api\V1\SaleAgreementController;
use App\Http\Controllers\Api\V1\MonthlyInvoiceController;
use App\Http\Controllers\Api\V1\MeterController;
use App\Http\Controllers\Api\V1\MeterReadingController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BillingOperationsController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\Auth\SetupController;
use App\Http\Controllers\Api\V1\Webhook\PaymentWebhookController; 

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public & Guest Core Routes
    |--------------------------------------------------------------------------
    */
    Route::get('system/bootstrap-status', [SystemController::class, 'bootstrapStatus']);
    Route::post('setup/company', [SetupController::class, 'registerCompany']);
    Route::post('login', [AuthController::class, 'login']);

    /*
    |--------------------------------------------------------------------------
    | Third-Party Webhooks (Exempt from Sanctum Auth)
    |--------------------------------------------------------------------------
    */
    // Route::post('webhooks/payments/evc-plus', [PaymentWebhookController::class, 'handleEvcPlus']);

    /*
    |--------------------------------------------------------------------------
    | Protected Corporate Core (Tenant Isolated)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Session Identity Status Checks
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // Structural Corporate Layers
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('buildings', BuildingController::class);
        
        Route::get('apartments/summary', [ApartmentController::class, 'summary']);
        Route::apiResource('apartments', ApartmentController::class);

        // Core Configuration Subsystems
        Route::apiResource('charge-types', ChargeTypeController::class);
        Route::post('charge-models/{charge_model}/clone', [ChargeModelController::class, 'clone'])
            ->name('api.v1.charge-models.clone');
        Route::apiResource('charge-models', ChargeModelController::class);

        Route::get('charges/summary', [ChargeController::class, 'summary'])
            ->name('api.v1.charges.summary');
        Route::post('charges/bulk-approve', [ChargeController::class, 'bulkApprove'])
            ->name('api.v1.charges.bulk-approve');
        Route::post('charges/{charge}/approve', [ChargeController::class, 'approve']);
        Route::post('charges/{charge}/reject', [ChargeController::class, 'reject']);
        Route::apiResource('charges', ChargeController::class)->only(['index', 'show']);

        // CRM / Stakeholder Management
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('buyers', BuyerController::class);

        /*
        |--------------------------------------------------------------------------
        | Multi-Tenant Billing Operations
        |--------------------------------------------------------------------------
        | Wrapped securely in a 'billing' prefix block. These endpoints resolve to:
        | - GET  /api/v1/billing/summary
        | - POST /api/v1/billing/generate
        |
        */
        Route::prefix('billing')->group(function () {
            Route::get('/pipeline-status', [BillingOperationsController::class, 'pipelineStatus'])
                ->name('api.v1.billing.pipeline-status');

            Route::get('/summary', [BillingOperationsController::class, 'summary'])
                ->name('api.v1.billing.summary');

            Route::post('/generate', [BillingOperationsController::class, 'triggerConsolidation'])
                ->name('api.v1.billing.generate');
        });

        Route::get('payments/tenant-balance', [PaymentController::class, 'tenantBalance'])
            ->name('api.v1.payments.tenant-balance');
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store']);

        // Contractual Lease Agreements
        Route::apiResource('rental-agreements', RentalAgreementController::class);
        Route::post('rental-agreements/{rentalAgreement}/approve', [RentalAgreementController::class, 'approve']);
        Route::post('rental-agreements/{rentalAgreement}/activate', [RentalAgreementController::class, 'activate']);
        Route::post('rental-agreements/{rentalAgreement}/terminate', [RentalAgreementController::class, 'terminate']);
        Route::post('rental-agreements/{rentalAgreement}/consolidate-billing', [RentalAgreementController::class, 'consolidateBilling'])
            ->name('api.v1.rental-agreements.consolidate-billing');
        
        Route::apiResource('sale-agreements', SaleAgreementController::class);

        /*
        |--------------------------------------------------------------------------
        | Invoices & Financial Record Infrastructure
        |--------------------------------------------------------------------------
        */
        // Bulk Invoice Progress Tracking
        Route::get('invoices/batch-status', function (Request $request) {
            return response()->json([
                'status' => Cache::get('batch_status_' . $request->user()->id, 'idle')
            ]);
        })->name('api.v1.invoices.batch-status');

        Route::get('invoices/summary', [MonthlyInvoiceController::class, 'summary'])
            ->name('api.v1.invoices.summary');
        Route::post('invoices/bulk-issue', [MonthlyInvoiceController::class, 'bulkIssue'])
            ->name('api.v1.invoices.bulk-issue');
        Route::post('invoices/bulk-mark-paid', [MonthlyInvoiceController::class, 'bulkMarkPaid']);
        Route::get('invoices/{invoice}/download', [MonthlyInvoiceController::class, 'download']);
        Route::post('invoices/{invoice}/finalize', [MonthlyInvoiceController::class, 'finalize']);
        Route::post('invoices/{invoice}/void', [MonthlyInvoiceController::class, 'void'])
            ->name('api.v1.invoices.void');
        Route::apiResource('invoices', MonthlyInvoiceController::class);

        /*
        |--------------------------------------------------------------------------
        | Asset Meter Infrastructure
        |--------------------------------------------------------------------------
        */
        Route::apiResource('meters', MeterController::class);
        Route::prefix('meters/{meter}')->group(function () {
            Route::post('activate', [MeterController::class, 'activate']);
            Route::post('faulty', [MeterController::class, 'markFaulty']);
            Route::post('maintenance', [MeterController::class, 'maintenance']);
            Route::post('maintenance/complete', [MeterController::class, 'completeMaintenance']);
            Route::post('decommission', [MeterController::class, 'decommission']);
            Route::post('inspection/complete', [MeterController::class, 'completeInspection']);
        });

        // Operational Meter Pipeline 
        Route::get('meter-readings/anomalies', [MeterReadingController::class, 'anomalies']);
        Route::post('meter-readings/{meterReading}/approve', [MeterReadingController::class, 'approve']);
        Route::post('meter-readings/{meterReading}/reject', [MeterReadingController::class, 'reject']);
        Route::apiResource('meter-readings', MeterReadingController::class);

    });
});