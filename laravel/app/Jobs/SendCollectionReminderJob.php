<?php

namespace App\Jobs;

use App\Models\CollectionReminderLog;
use App\Services\Collections\CollectionReminderService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCollectionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [15, 30, 60, 120];

    public function __construct(
        public string $logId,
    ) {}

    public function handle(CollectionReminderService $reminders): void
    {
        $log = CollectionReminderLog::query()->find($this->logId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        TenantContext::setCompanyId((string) $log->company_id);

        $reminders->dispatchLog($log);
    }
}
