<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\InvoiceIssued;
use App\Listeners\GenerateInvoicePdf;
use App\Listeners\EmailInvoiceToTenant;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceIssued::class => [
            GenerateInvoicePdf::class,
            EmailInvoiceToTenant::class,
        ],
    ];
}