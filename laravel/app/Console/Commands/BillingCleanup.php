<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BillingRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

class BillingCleanup extends Command
{
    /**
     * The name and signature of the console command.
     * Uniformly configured via standard Laravel signature parsing format.
     *
     * @var string
     */
    protected $signature = 'billing:cleanup-stale-runs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans the platform infrastructure database to identify and fail async billing run tasks that have stalled past security time windows.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting background ledger system infrastructure audit...');
        
        /*
        |--------------------------------------------------------------------------
        | Look up Stalled Thread Failures
        |--------------------------------------------------------------------------
        | Identifies runs stuck in a processing loop status for longer than a 2-hour 
        | safety threshold. This decouples the maintenance sanitation process 
        | entirely from your standard application request cycles.
        |--------------------------------------------------------------------------
        */
        $staleThreshold = now()->subHours(2);

        try {
            // Find records to be targeted to provide verbose console debugging output
            $staleQuery = BillingRun::where('status', 'running')
                ->where('execution_started_at', '<', $staleThreshold);

            $staleCount = $staleQuery->count();

            if ($staleCount === 0) {
                $this->info('Sanitation check complete: All executing batch run threads are healthy.');
                return SymfonyCommand::SUCCESS;
            }

            $this->warn("Sanitation match confirmed: Found {$staleCount} zombie billing runs stalled on the ledger.");

            // Bulk update stale records into deterministic failure tracking metrics
            $updatedRows = $staleQuery->update([
                'status'                 => 'failed',
                'execution_completed_at' => now(),
                'error_summary'          => 'Terminated by automated infrastructure system console cleanup routine due to execution timeout.'
            ]);

            $this->info("Successfully terminated and marked {$updatedRows} processes as failed.");
            
            Log::warning('Automated background maintenance routine cleared out zombie processes.', [
                'stale_records_found' => $staleCount,
                'affected_rows'       => $updatedRows,
                'remediation_action'  => 'FORCE_STATUS_FAILURE_TIMEOUT'
            ]);

        } catch (Throwable $exception) {
            Log::critical('Infrastructure maintenance sweep failed to evaluate database log structures.', [
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString()
            ]);

            $this->error('Fatal: Failed to execute system maintenance checks. Check system logs for breakdown trace details.');
            return SymfonyCommand::FAILURE;
        }

        return SymfonyCommand::SUCCESS;
    }
}