<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Sales\InstallmentBillingService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class PostInstallmentInvoices extends Command
{
    protected $signature = 'sales:post-installment-invoices
                            {--company_id= : Limit to one company UUID}
                            {--as-of= : Post instalments due on or before this date (Y-m-d)}';

    protected $description = 'Issue buyer invoices for due sale instalments and mark overdue lines.';

    public function handle(InstallmentBillingService $billing): int
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

        $totals = ['posted' => 0, 'skipped' => 0, 'overdue' => 0];

        foreach ($companies as $company) {
            TenantContext::setCompanyId((string) $company->id);

            $stats = $billing->postDueInvoices((string) $company->id, $asOf);

            foreach ($stats as $key => $value) {
                $totals[$key] += $value;
            }

            $this->line(sprintf(
                'Company %s: posted=%d skipped=%d overdue=%d',
                $company->id,
                $stats['posted'],
                $stats['skipped'],
                $stats['overdue'],
            ));
        }

        $this->info(sprintf(
            'Done. posted=%d skipped=%d overdue=%d (as of %s)',
            $totals['posted'],
            $totals['skipped'],
            $totals['overdue'],
            $asOf->toDateString(),
        ));

        return SymfonyCommand::SUCCESS;
    }
}
