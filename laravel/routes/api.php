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
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\TrialBalanceController;
use App\Http\Controllers\Api\V1\IncomeStatementController;
use App\Http\Controllers\Api\V1\BalanceSheetController;
use App\Http\Controllers\Api\V1\FinancialAuditController;
use App\Http\Controllers\Api\V1\ChargeTypeController; 
use App\Http\Controllers\Api\V1\ChargeController;
use App\Http\Controllers\Api\V1\ChargeModelController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ApartmentController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\BuyerController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\RentalAgreementController;
use App\Http\Controllers\Api\V1\SaleAgreementController;
use App\Http\Controllers\Api\V1\SaleReservationController;
use App\Http\Controllers\Api\V1\MonthlyInvoiceController;
use App\Http\Controllers\Api\V1\MeterController;
use App\Http\Controllers\Api\V1\MeterReadingController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BillingOperationsController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportsController;
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
        Route::get('accounts/{account}/ledger/export', [AccountController::class, 'ledgerExport'])
            ->name('api.v1.accounts.ledger.export');
        Route::get('accounts/{account}/ledger', [AccountController::class, 'ledger'])
            ->name('api.v1.accounts.ledger');
        Route::get('trial-balance/export', [TrialBalanceController::class, 'export'])
            ->name('api.v1.trial-balance.export');
        Route::post('trial-balance/close-period', [TrialBalanceController::class, 'closePeriod'])
            ->name('api.v1.trial-balance.close-period');
        Route::get('trial-balance', [TrialBalanceController::class, 'index'])
            ->name('api.v1.trial-balance.index');
        Route::get('income-statement/export-pdf', [IncomeStatementController::class, 'exportPdf'])
            ->name('api.v1.income-statement.export-pdf');
        Route::get('income-statement/export', [IncomeStatementController::class, 'export'])
            ->name('api.v1.income-statement.export');
        Route::get('income-statement', [IncomeStatementController::class, 'index'])
            ->name('api.v1.income-statement.index');
        Route::get('balance-sheet/export-pdf', [BalanceSheetController::class, 'exportPdf'])
            ->name('api.v1.balance-sheet.export-pdf');
        Route::get('balance-sheet/export', [BalanceSheetController::class, 'export'])
            ->name('api.v1.balance-sheet.export');
        Route::get('balance-sheet', [BalanceSheetController::class, 'index'])
            ->name('api.v1.balance-sheet.index');
        Route::get('financial-audit/export', [FinancialAuditController::class, 'export'])
            ->name('api.v1.financial-audit.export');
        Route::get('financial-audit', [FinancialAuditController::class, 'index'])
            ->name('api.v1.financial-audit.index');
        Route::apiResource('accounts', AccountController::class);
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
        Route::get('tenants/{tenant}/billing', [TenantController::class, 'billing'])
            ->name('api.v1.tenants.billing');
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('buyers', BuyerController::class);
        Route::get('inventory/available', [InventoryController::class, 'available'])
            ->name('api.v1.inventory.available');
        Route::get('apartments/{apartment}/ownership-history', [ApartmentController::class, 'ownershipHistory'])
            ->name('api.v1.apartments.ownership-history');
        Route::get('apartments/{apartment}/inventory-history', [InventoryController::class, 'history'])
            ->name('api.v1.apartments.inventory-history');

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

        Route::get('payments/receipt-account-options', [PaymentController::class, 'receiptAccountOptions']);
        Route::get('payments/tenant-balance', [PaymentController::class, 'tenantBalance'])
            ->name('api.v1.payments.tenant-balance');
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);

        Route::prefix('reports')->group(function () {
            Route::get('aging', [ReportsController::class, 'aging'])
                ->name('api.v1.reports.aging');
            Route::get('aging/export', [ReportsController::class, 'agingExport'])
                ->name('api.v1.reports.aging.export');
            Route::get('delinquency', [ReportsController::class, 'delinquency'])
                ->name('api.v1.reports.delinquency');
            Route::post('delinquency/remind', [ReportsController::class, 'sendReminders'])
                ->name('api.v1.reports.delinquency.remind');
            Route::post('delinquency/notices', [ReportsController::class, 'generateNotice'])
                ->name('api.v1.reports.delinquency.notices');
            Route::get('notices/{notice}/download', [ReportsController::class, 'downloadNotice'])
                ->name('api.v1.reports.notices.download');
            Route::get('reminder-logs', [ReportsController::class, 'reminderLogs'])
                ->name('api.v1.reports.reminder-logs');
        });

        // Contractual Lease Agreements
        Route::apiResource('rental-agreements', RentalAgreementController::class);
        Route::post('rental-agreements/{rentalAgreement}/approve', [RentalAgreementController::class, 'approve']);
        Route::post('rental-agreements/{rentalAgreement}/activate', [RentalAgreementController::class, 'activate']);
        Route::post('rental-agreements/{rentalAgreement}/terminate', [RentalAgreementController::class, 'terminate']);
        Route::post('rental-agreements/{rentalAgreement}/consolidate-billing', [RentalAgreementController::class, 'consolidateBilling'])
            ->name('api.v1.rental-agreements.consolidate-billing');
        Route::post('rental-agreements/{rentalAgreement}/apply-deposit', [RentalAgreementController::class, 'applyDeposit'])
            ->name('api.v1.rental-agreements.apply-deposit');
        
        Route::apiResource('sale-reservations', SaleReservationController::class)->only(['index', 'store', 'show']);
        Route::post('sale-reservations/{sale_reservation}/deposit', [SaleReservationController::class, 'recordDeposit'])
            ->name('api.v1.sale-reservations.deposit');
        Route::post('sale-reservations/{sale_reservation}/cancel', [SaleReservationController::class, 'cancel'])
            ->name('api.v1.sale-reservations.cancel');

        Route::apiResource('sale-agreements', SaleAgreementController::class);
        Route::post('sale-agreements/{sale_agreement}/execute', [SaleAgreementController::class, 'execute'])
            ->name('api.v1.sale-agreements.execute');
        Route::post('sale-agreements/{sale_agreement}/cancel', [SaleAgreementController::class, 'cancel'])
            ->name('api.v1.sale-agreements.cancel');
        Route::post('sale-agreements/{sale_agreement}/record-payment', [SaleAgreementController::class, 'recordPayment'])
            ->name('api.v1.sale-agreements.record-payment');
        Route::post('sale-agreements/{sale_agreement}/apply-deposit', [SaleAgreementController::class, 'applyDeposit'])
            ->name('api.v1.sale-agreements.apply-deposit');
        Route::get('sale-agreements/{sale_agreement}/completion-certificate', [SaleAgreementController::class, 'downloadCompletionCertificate'])
            ->name('api.v1.sale-agreements.completion-certificate');
        Route::post('sale-agreements/{sale_agreement}/generate-schedule', [SaleAgreementController::class, 'generateSchedule'])
            ->name('api.v1.sale-agreements.generate-schedule');
        Route::post('sale-agreements/{sale_agreement}/record-installment-payment', [SaleAgreementController::class, 'recordInstallmentPayment'])
            ->name('api.v1.sale-agreements.record-installment-payment');
        Route::post('sale-agreements/{sale_agreement}/approve-ownership', [SaleAgreementController::class, 'approveOwnership'])
            ->name('api.v1.sale-agreements.approve-ownership');
        Route::post('sale-agreements/{sale_agreement}/issue-title-deed', [SaleAgreementController::class, 'issueTitleDeed'])
            ->name('api.v1.sale-agreements.issue-title-deed');
        Route::get('sale-agreements/{sale_agreement}/ownership-transfer-certificate', [SaleAgreementController::class, 'downloadOwnershipTransferCertificate'])
            ->name('api.v1.sale-agreements.ownership-transfer-certificate');
        Route::get('sale-agreements/{sale_agreement}/sales-contract', [SaleAgreementController::class, 'downloadSalesContract'])
            ->name('api.v1.sale-agreements.sales-contract');
        Route::get('sale-agreements/{sale_agreement}/payment-plan-statement', [SaleAgreementController::class, 'downloadPaymentPlanStatement'])
            ->name('api.v1.sale-agreements.payment-plan-statement');
        Route::get('sale-agreements/{sale_agreement}/installment-schedule', [SaleAgreementController::class, 'downloadInstallmentSchedule'])
            ->name('api.v1.sale-agreements.installment-schedule');

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
        Route::get('meter-readings/entry-grid', [MeterReadingController::class, 'entryGrid']);
        Route::post('meter-readings/bulk', [MeterReadingController::class, 'bulkStore']);
        Route::post('meter-readings/bulk-approve', [MeterReadingController::class, 'bulkApprove']);
        Route::post('meter-readings/{meterReading}/approve', [MeterReadingController::class, 'approve']);
        Route::post('meter-readings/{meterReading}/reject', [MeterReadingController::class, 'reject']);
        Route::apiResource('meter-readings', MeterReadingController::class);

    });
});