<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfGeneratorService
{
    public function generate(MonthlyInvoice $invoice): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::info('DomPDF not installed; skipping invoice PDF generation.', [
                'invoice_id' => $invoice->id,
            ]);

            return null;
        }

        if (! view()->exists('invoices.pdf_template')) {
            Log::warning('Invoice PDF template missing; skipping generation.', [
                'invoice_id' => $invoice->id,
            ]);

            return null;
        }

        $invoice->loadMissing(['lineItems', 'apartment.building']);

        $company = Company::query()->find($invoice->company_id);
        $tenantName = $this->resolveTenantName($invoice);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf_template', [
            'invoice' => $invoice,
            'lineItems' => $invoice->lineItems,
            'company' => $company,
            'tenantName' => $tenantName,
        ]);

        $path = "invoices/{$invoice->company_id}/{$invoice->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
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
        if ($display !== '') {
            return $display;
        }

        return trim(collect([$tenant->first_name, $tenant->last_name])->filter()->implode(' ')) ?: 'Tenant';
    }
}
