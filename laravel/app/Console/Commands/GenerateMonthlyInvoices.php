<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\Billing\BillingPipelineService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'billing:generate-monthly
                            {--company_id= : Tenant company UUID (required)}
                            {--year= : Target year (YYYY)}
                            {--month= : Target month (1-12)}
                            {--skip-recurring : Only consolidate; do not run recurring rent/fees}';

    protected $description = 'Run monthly billing close: recurring charges + invoice consolidation per active agreement.';

    public function handle(): int
    {
        $companyId = $this->option('company_id');

        if (! $companyId) {
            $this->error('Aborted: --company_id is required for multi-tenant isolation.');

            return SymfonyCommand::FAILURE;
        }

        $company = Company::query()->find($companyId);

        if (! $company) {
            $this->error("Company not found: {$companyId}");

            return SymfonyCommand::FAILURE;
        }

        TenantContext::setCompanyId((string) $company->id);

        $user = User::query()
            ->where('company_id', $company->id)
            ->orderBy('created_at')
            ->first();

        if (! $user) {
            $this->error("No user found for company {$company->id} — cannot run billing pipeline.");

            return SymfonyCommand::FAILURE;
        }

        $year = $this->option('year') ?? now()->year;
        $month = $this->option('month') ?? now()->month;

        if (! checkdate((int) $month, 1, (int) $year)) {
            $this->error("Invalid period: year={$year}, month={$month}");

            return SymfonyCommand::FAILURE;
        }

        $billingDate = Carbon::create((int) $year, (int) $month, 1)->startOfMonth();
        $generateRecurring = ! $this->option('skip-recurring');

        $this->info('======================================================================');
        $this->info(' Monthly billing close');
        $this->info(' Period        : '.$billingDate->format('F Y'));
        $this->info(' Company       : '.$company->name.' ('.$company->id.')');
        $this->info(' Recurring run : '.($generateRecurring ? 'yes' : 'skipped'));
        $this->info('======================================================================');

        $pipeline = app(BillingPipelineService::class, ['user' => $user]);
        $result = $pipeline->runMonthlyClose($billingDate, $generateRecurring);

        $consolidation = $result['consolidation'];
        $pipelineStatus = $result['pipeline'];

        $this->newLine();
        $this->info('Billing run');
        if ($result['billing_run']) {
            $run = $result['billing_run'];
            $this->line("  Run #{$run['run_number']} — status: {$run['status']}");
        } else {
            $this->line('  Skipped (consolidation only)');
        }

        $this->newLine();
        $this->info('Consolidation');
        $this->line('  Created / updated : '.($consolidation['success'] ?? 0));
        $this->line('  Appended          : '.($consolidation['appended'] ?? 0));
        $this->line('  Skipped           : '.($consolidation['skipped'] ?? 0));
        $this->line('  Draft invoices    : '.($consolidation['draft_invoices_for_period'] ?? 0));

        if (($consolidation['failed'] ?? 0) > 0) {
            $this->newLine();
            $this->error('  Failed: '.$consolidation['failed']);
            foreach ($consolidation['errors'] ?? [] as $error) {
                $this->line('    - '.($error['agreement_number'] ?? $error['agreement_id']).': '.$error['message']);
            }

            return SymfonyCommand::INVALID;
        }

        $pendingUtilities = $pipelineStatus['blocking_pending_utility_charges'] ?? 0;
        if ($pendingUtilities > 0) {
            $this->newLine();
            $this->warn("{$pendingUtilities} utility charge(s) still await approval and were excluded.");
        }

        $this->newLine();
        $this->info('Monthly billing close completed.');

        return SymfonyCommand::SUCCESS;
    }
}
