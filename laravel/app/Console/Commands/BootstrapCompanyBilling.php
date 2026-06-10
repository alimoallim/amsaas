<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\Billing\BillingCompanyBootstrapService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class BootstrapCompanyBilling extends Command
{
    protected $signature = 'billing:bootstrap-company
                            {--company_id= : Company UUID (required)}
                            {--year= : Billing year for optional close}
                            {--month= : Billing month for optional close}
                            {--run-close : Run monthly billing close after setup}
                            {--no-backfill : Skip utility charge backfill from approved readings}';

    protected $description = 'Ensure rent charge model, fix utility models, sync agreement charges, optionally run billing close.';

    public function handle(BillingCompanyBootstrapService $bootstrap): int
    {
        $companyId = $this->option('company_id');

        if (! $companyId) {
            $this->error('--company_id is required.');

            return SymfonyCommand::FAILURE;
        }

        $company = Company::query()->find($companyId);

        if (! $company) {
            $this->error("Company not found: {$companyId}");

            return SymfonyCommand::FAILURE;
        }

        $actor = User::query()
            ->where('company_id', $company->id)
            ->orderBy('created_at')
            ->first();

        if (! $actor) {
            $this->error('No user found for this company.');

            return SymfonyCommand::FAILURE;
        }

        TenantContext::setCompanyId((string) $company->id);

        $year = (int) ($this->option('year') ?? now()->year);
        $month = (int) ($this->option('month') ?? now()->month);
        $billingDate = Carbon::create($year, $month, 1)->startOfMonth();

        $this->info("Bootstrapping billing for {$company->name} ({$company->id})");
        $this->info('Period: '.$billingDate->format('F Y'));

        $result = $bootstrap->bootstrap(
            $company,
            $actor,
            $billingDate,
            (bool) $this->option('run-close'),
            ! $this->option('no-backfill'),
        );

        if ($result['rent_charge_model']) {
            $m = $result['rent_charge_model'];
            $this->line('Rent model: '.$m['code'].' — '.$m['name']);
        } else {
            $this->warn('No rent charge model (create a charge type first).');
        }

        $this->line('Fixed utility meter_type on '.$result['fixed_charge_models'].' model(s).');
        $this->line('Synced agreement charges for '.$result['agreement_charges_synced'].' active lease(s).');
        $this->line('Backfilled '.$result['utility_charges_backfilled'].' utility charge(s) from approved readings.');

        if ($result['monthly_close']) {
            $close = $result['monthly_close'];
            $this->info('Monthly close — created/updated: '.($close['success'] ?? 0)
                .', skipped: '.($close['skipped'] ?? 0)
                .', failed: '.($close['failed'] ?? 0));
        }

        $this->info('Bootstrap complete.');

        return SymfonyCommand::SUCCESS;
    }
}
