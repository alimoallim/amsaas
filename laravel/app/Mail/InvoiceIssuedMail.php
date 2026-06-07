<?php

namespace App\Mail;

use App\Models\MonthlyInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonthlyInvoice $invoice,
        public string $tenantName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Invoice %s — %s/%s', $this->invoice->invoice_number, $this->invoice->billing_month, $this->invoice->billing_year),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice_issued',
            with: [
                'invoice' => $this->invoice,
                'tenantName' => $this->tenantName,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->invoice->file_path) {
            return [];
        }

        $fullPath = storage_path('app/'.$this->invoice->file_path);
        if (! is_file($fullPath)) {
            return [];
        }

        return [
            Attachment::fromPath($fullPath)
                ->as($this->invoice->invoice_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
