<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Services\Billing\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued fallback when PDF was not generated synchronously on issue (e.g. worker retry).
 */
class GenerateInvoicePdf implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(InvoiceIssued $event): void
    {
        app(\App\Services\MultiTenancy\TenancyManager::class)
            ->setCompanyId($event->invoice->company_id);

        app(InvoicePdfService::class)->ensureReady($event->invoice);
    }
}
