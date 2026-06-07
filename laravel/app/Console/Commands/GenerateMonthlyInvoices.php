<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RentalAgreement;
use App\Services\Billing\InvoiceConsolidationService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     * Enforces explicit tenant isolation boundaries alongside temporal overrides.
     *
     * @var string
     */
    protected $signature = 'billing:generate-monthly
                            {--company_id= : The explicit ID of the tenant company to isolate execution}
                            {--year= : Optional target processing year override (Format: YYYY)}
                            {--month= : Optional target processing month override (Format: MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolidates all pending unposted billing items and dynamic utility charges into finalized invoices per active contract.';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceConsolidationService $consolidationService): int
    {
        $companyId = $this->option('company_id');

        // 1. Strict Multi-Tenant Pre-flight Verification Guardrail
        if (!$companyId || !is_numeric($companyId)) {
            $this->error('Aborted: Multi-tenancy safeguard triggered. You must provide a valid numeric --company_id.');
            return SymfonyCommand::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Establish Safe CLI Tenant Execution Context
        |--------------------------------------------------------------------------
        | Binds the targeted company ID into the service container. This explicitly
        | activates your CompanyScope and BelongsToCompany traits for all database
        | connections instantiated during this execution lifecycle.
        |--------------------------------------------------------------------------
        */
        TenantContext::setCompanyId((string) $companyId);

        // 2. Parse and Validate Date Parameters
        $year = $this->option('year') ?? now()->year;
        $month = $this->option('month') ?? now()->month;

        if (!checkdate((int) $month, 1, (int) $year)) {
            $this->error("Invalid temporal parameters provided: Month ({$month}) or Year ({$year}) out of bounds.");
            return SymfonyCommand::FAILURE;
        }

        $billingDate = Carbon::create((int) $year, (int) $month, 1)->startOfMonth();
        
        $this->info("======================================================================");
        $this->info(" Starting Billing Engine Pipeline Consolidation");
        $this->info(" Target Period : " . $billingDate->format('F Y'));
        $this->info(" Company Context: ID {$companyId}");
        $this->info("======================================================================");

        // 3. Eager-Load Graph Framework to Mitigate N+1 Memory Exhaustion
        $agreementsQuery = RentalAgreement::query()
            ->where('status', 'active')
            ->with([
                'tenant', 
                'apartment.building'
            ]);

        $totalAgreements = $agreementsQuery->count();

        if ($totalAgreements === 0) {
            $this->warn('Execution complete: Zero active rental agreements matched this company runtime scope.');
            return SymfonyCommand::SUCCESS;
        }

        $this->info("Dispatched processing queues for {$totalAgreements} candidate lease structures...");
        
        $progressBar = $this->output->createProgressBar($totalAgreements);
        $progressBar->start();

        $metrics = [
            'success' => 0,
            'skipped' => 0,
            'failed'  => 0,
        ];

        // 4. Memory-Safe Chunking to Prevent Background Deallocation Collapses
        $agreementsQuery->chunk(100, function ($agreements) use ($consolidationService, $billingDate, $progressBar, &$metrics) {
            foreach ($agreements as $agreement) {
                try {
                    /*
                    |--------------------------------------------------------------------------
                    | Process Single Isolated Invoice Generation Boundary
                    |--------------------------------------------------------------------------
                    | Internal database transactions are evaluated individually inside the
                    | domain service level. A single contract failure will not crash or roll
                    | back the entire macro batch process run.
                    |--------------------------------------------------------------------------
                    */
                    $outcome = $consolidationService->consolidate($agreement, $billingDate);

                    if ($outcome->wasCreated()) {
                        $metrics['success']++;
                    } else {
                        $metrics['skipped']++;
                    }
                } catch (Throwable $exception) {
                    $metrics['failed']++;
                    
                    Log::error('Critical billing engine processing failure for contract asset.', [
                        'company_id'          => app('tenant.current_id'),
                        'rental_agreement_id' => $agreement->id,
                        'apartment_id'        => $agreement->apartment_id,
                        'message'             => $exception->getMessage(),
                        'trace'               => $exception->getTraceAsString(),
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // 5. Render Command Execution Summary
        $this->info("======================================================================");
        $this->info(" Execution Summary Metrics");
        $this->info("======================================================================");
        $this->line(" Successfully Consolidated & Issued Invoices: " . $metrics['success']);
        $this->line(" Skipped (Zero Balance Pending Line Items)  : " . $metrics['skipped']);
        
        if ($metrics['failed'] > 0) {
            $this->error(" Failed Processing Runs (Check Error Logs)   : " . $metrics['failed']);
            return SymfonyCommand::INVALID;
        }

        $this->info("Pipeline tracking loop completed cleanly.");
        return SymfonyCommand::SUCCESS;
    }
}