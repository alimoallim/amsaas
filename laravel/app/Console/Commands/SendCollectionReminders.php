<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Collections\CollectionReminderService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class SendCollectionReminders extends Command
{
    protected $signature = 'collections:send-reminders
                            {--company_id= : Limit to one company UUID}
                            {--as-of= : Evaluate schedule as of date (Y-m-d)}';

    protected $description = 'Queue payment reminder emails for invoices matching the collection schedule.';

    public function handle(CollectionReminderService $reminders): int
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

        $this->info('Queuing collection reminders as of '.$asOf->toDateString());

        $totals = ['queued' => 0, 'skipped' => 0];

        foreach ($companies as $company) {
            TenantContext::setCompanyId((string) $company->id);

            $stats = $reminders->queueDueReminders($company, $asOf);
            $totals['queued'] += $stats['queued'];
            $totals['skipped'] += $stats['skipped'];

            $this->line(sprintf(
                '  %s — queued: %d, skipped: %d',
                $company->name,
                $stats['queued'],
                $stats['skipped'],
            ));
        }

        $this->info(sprintf('Done — %d queued, %d skipped.', $totals['queued'], $totals['skipped']));

        return SymfonyCommand::SUCCESS;
    }
}
