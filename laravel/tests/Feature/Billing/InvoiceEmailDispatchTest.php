<?php

namespace Tests\Feature\Billing;

use App\Events\InvoiceIssued;
use App\Listeners\EmailInvoiceToTenant;
use App\Mail\InvoiceIssuedMail;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceEmailDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_listener_sends_mail_when_pdf_exists(): void
    {
        Mail::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create([
            'company_id' => $company->id,
            'email' => 'tenant@example.com',
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
        ]);

        $path = "invoices/{$company->id}/test.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'AMS-2026-00099',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 500,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
            'file_path' => $path,
        ]);

        $listener = app(EmailInvoiceToTenant::class);
        $listener->handle(new InvoiceIssued($invoice));

        Mail::assertSent(InvoiceIssuedMail::class, function (InvoiceIssuedMail $mail) {
            return $mail->hasTo('tenant@example.com');
        });

        $this->assertEquals('sent', $invoice->fresh()->dispatch_status);
    }

    public function test_email_listener_skips_when_tenant_has_no_email(): void
    {
        Mail::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create([
            'company_id' => $company->id,
            'email' => '',
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
        ]);

        $path = "invoices/{$company->id}/test.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'AMS-2026-00100',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 500,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
            'file_path' => $path,
        ]);

        app(EmailInvoiceToTenant::class)->handle(new InvoiceIssued($invoice));

        Mail::assertNothingSent();
        $this->assertEquals('skipped_no_email', $invoice->fresh()->dispatch_status);
    }
}
