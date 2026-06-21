<?php

namespace App\Services\Billing;

use App\Models\MonthlyInvoice;
use App\Services\PdfGeneratorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /** @var array<int, string> */
    private const DOWNLOADABLE_STATUSES = [
        'issued',
        'finalized',
        'partially_paid',
        'paid',
        'overdue',
    ];

    public function __construct(
        protected PdfGeneratorService $pdfGenerator,
    ) {}

    public function isDownloadable(MonthlyInvoice $invoice): bool
    {
        return in_array($invoice->status, self::DOWNLOADABLE_STATUSES, true);
    }

    /**
     * Return stored PDF path, generating synchronously when missing.
     */
    public function ensureReady(MonthlyInvoice $invoice): ?string
    {
        $invoice->refresh();

        if ($this->storedPathExists($invoice->file_path)) {
            return $invoice->file_path;
        }

        if (! $this->isDownloadable($invoice)) {
            return null;
        }

        try {
            $path = $this->pdfGenerator->generate($invoice);

            if (! $path) {
                return null;
            }

            $invoice->update([
                'file_path' => $path,
                'dispatch_status' => $invoice->dispatch_status ?: 'pdf_ready',
            ]);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Invoice PDF generation failed.', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function storedPathExists(?string $path): bool
    {
        return filled($path) && Storage::disk('local')->exists($path);
    }
}
