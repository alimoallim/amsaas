<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Sales\ReservationService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ExpireSaleReservations extends Command
{
    protected $signature = 'sales:expire-reservations
                            {--company_id= : Limit to one company UUID}
                            {--as-of= : Evaluate as of date (Y-m-d), defaults to today}';

    protected $description = 'Expire unpaid sale reservations past expiry and release units.';

    public function handle(ReservationService $reservations): int
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

        $totals = ['expired' => 0, 'released' => 0];

        foreach ($companies as $company) {
            TenantContext::setCompanyId((string) $company->id);

            $stats = $reservations->expireDueReservations($asOf, (string) $company->id);

            foreach ($stats as $key => $value) {
                $totals[$key] += $value;
            }

            $this->line(sprintf(
                'Company %s: expired=%d released=%d',
                $company->id,
                $stats['expired'],
                $stats['released'],
            ));
        }

        $this->info(sprintf(
            'Done. expired=%d released=%d (as of %s)',
            $totals['expired'],
            $totals['released'],
            $asOf->toDateString(),
        ));

        return SymfonyCommand::SUCCESS;
    }
}
