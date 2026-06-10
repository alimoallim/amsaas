<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Collections\DelinquencyTrackingService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class FlagOverdueInvoices extends Command
{
    protected $signature = 'collections:flag-overdue
                            {--company_id= : Limit to one company UUID}
                            {--as-of= : Evaluate as of date (Y-m-d), defaults to today}';

    protected $description = 'Mark past-due open invoices as overdue and maintain delinquency flags.';

    public function handle(DelinquencyTrackingService $tracking): int
    {
        $asOf = $this->option('as-of')
            ? Carbon::parse($this->option('as-of'))->startOfDay()
            : now()->startOfDay();

        $companyId = $this->option('company_id');

        $companies = $companyId
            ? Company::query()->where('id', $companyId)->get()
            : Company::query()->where('is_active', true)->get();

        if ($companies->isEmpty()) {
            $this->error('No companies matched.');

            return SymfonyCommand::FAILURE;
        }

        $this->info('Flagging overdue invoices as of '.$asOf->toDateString());

        $totals = ['flagged' => 0, 'created' => 0, 'escalated' => 0, 'status_updated' => 0];

        foreach ($companies as $company) {
            TenantContext::setCompanyId((string) $company->id);

            $stats = $tracking->processCompany($company, $asOf);

            foreach ($stats as $key => $value) {
                $totals[$key] += $value;
            }

            $this->line(sprintf(
                '  %s — flagged: %d, new flags: %d, escalated: %d, status updates: %d',
                $company->name,
                $stats['flagged'],
                $stats['created'],
                $stats['escalated'],
                $stats['status_updated'],
            ));
        }

        $this->info(sprintf(
            'Done — %d invoice(s) processed, %d new flag(s), %d escalation(s).',
            $totals['flagged'],
            $totals['created'],
            $totals['escalated'],
        ));

        return SymfonyCommand::SUCCESS;
    }
}
