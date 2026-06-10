<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class SeedChartOfAccounts extends Command
{
    protected $signature = 'accounting:seed-chart
                            {--company_id= : Company UUID (omit to seed all companies)}';

    protected $description = 'Seed default chart of accounts for one or all companies.';

    public function handle(ChartOfAccountsService $service): int
    {
        $companyId = $this->option('company_id');

        $companies = $companyId
            ? Company::query()->where('id', $companyId)->get()
            : Company::query()->orderBy('name')->get();

        if ($companies->isEmpty()) {
            $this->error('No companies found.');

            return SymfonyCommand::FAILURE;
        }

        foreach ($companies as $company) {
            TenantContext::setCompanyId((string) $company->id);

            $actor = User::query()
                ->where('company_id', $company->id)
                ->orderBy('created_at')
                ->value('id');

            $result = $service->seedDefaults($company, $actor);

            $this->line(sprintf(
                '%s — created: %d, updated: %d, skipped: %d',
                $company->name,
                $result['created'],
                $result['updated'],
                $result['skipped'],
            ));
        }

        $this->info('Chart of accounts seed complete.');

        return SymfonyCommand::SUCCESS;
    }
}
