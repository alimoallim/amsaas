<?php

namespace App\Mail;

use App\Enums\CollectionReminderType;
use App\Models\MonthlyInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollectionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonthlyInvoice $invoice,
        public string $tenantName,
        public CollectionReminderType $reminderType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Payment reminder — Invoice %s',
                $this->invoice->invoice_number,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.collection_reminder',
            with: [
                'invoice' => $this->invoice,
                'tenantName' => $this->tenantName,
                'reminderLabel' => $this->reminderType->label(),
            ],
        );
    }
}
