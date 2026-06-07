<?php

namespace Tests\Unit\Events;

use App\Events\InvoiceIssued;
use App\Listeners\EmailInvoiceToTenant;
use App\Listeners\GenerateInvoicePdf;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvoiceIssuedListenersTest extends TestCase
{
    public function test_invoice_issued_event_has_registered_listeners(): void
    {
        Event::fake();

        Event::assertListening(
            InvoiceIssued::class,
            GenerateInvoicePdf::class,
        );

        Event::assertListening(
            InvoiceIssued::class,
            EmailInvoiceToTenant::class,
        );
    }
}
