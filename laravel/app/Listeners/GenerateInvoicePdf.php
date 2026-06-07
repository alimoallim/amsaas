<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Services\PdfGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class GenerateInvoicePdf implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(protected PdfGeneratorService $pdfService) {}

    public function handle(InvoiceIssued $event): void
    {
        try {
            app(\App\Services\MultiTenancy\TenancyManager::class)
                ->setCompanyId($event->invoice->company_id);

            $path = $this->pdfService->generate($event->invoice);

            if ($path) {
                $event->invoice->update([
                    'file_path' => $path,
                    'dispatch_status' => 'pdf_ready',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Invoice PDF generation failed.', [
                'invoice_id' => $event->invoice->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
