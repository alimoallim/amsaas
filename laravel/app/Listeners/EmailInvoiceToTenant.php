<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Mail\InvoiceIssuedMail;
use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailInvoiceToTenant implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [15, 30, 60, 120];

    public function handle(InvoiceIssued $event): void
    {
        app(\App\Services\MultiTenancy\TenancyManager::class)
            ->setCompanyId($event->invoice->company_id);

        $invoice = MonthlyInvoice::query()
            ->with(['lineItems', 'apartment.building'])
            ->find($event->invoice->id);

        if (! $invoice) {
            return;
        }

        if (! $invoice->file_path) {
            $this->release(15);

            return;
        }

        $recipient = $this->resolveTenantEmail($invoice);
        if (! $recipient) {
            $invoice->update(['dispatch_status' => 'skipped_no_email']);

            return;
        }

        $tenantName = $this->resolveTenantName($invoice);

        try {
            Mail::to($recipient)->send(new InvoiceIssuedMail($invoice, $tenantName));
            $invoice->update(['dispatch_status' => 'sent']);
        } catch (\Throwable $e) {
            $invoice->update(['dispatch_status' => 'failed']);
            Log::warning('Invoice email dispatch failed.', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function resolveTenantEmail(MonthlyInvoice $invoice): ?string
    {
        if ($invoice->contract_type !== 'rental') {
            return null;
        }

        $agreement = Agreement::query()
            ->with('tenant:id,email,display_name,first_name,last_name')
            ->find($invoice->contract_id);

        $email = trim((string) ($agreement?->tenant?->email ?? ''));

        return $email !== '' ? $email : null;
    }

    protected function resolveTenantName(MonthlyInvoice $invoice): string
    {
        if ($invoice->contract_type !== 'rental') {
            return 'Tenant';
        }

        $agreement = Agreement::query()
            ->with('tenant:id,display_name,first_name,last_name')
            ->find($invoice->contract_id);

        $tenant = $agreement?->tenant;
        if (! $tenant) {
            return 'Tenant';
        }

        $display = trim((string) ($tenant->display_name ?? ''));

        return $display !== ''
            ? $display
            : (trim(collect([$tenant->first_name, $tenant->last_name])->filter()->implode(' ')) ?: 'Tenant');
    }
}
