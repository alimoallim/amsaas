<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_invoice_pdf_is_generated_on_download_without_queue_worker(): void
    {
        Storage::fake('local');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->draftInvoice($company);

        app(InvoiceService::class)->issue($invoice);

        $invoice->refresh();
        $this->assertNotNull($invoice->file_path);
        Storage::disk('local')->assertExists($invoice->file_path);

        $response = $this->get("/api/v1/invoices/{$invoice->id}/download");

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = method_exists($response, 'streamedContent')
            ? $response->streamedContent()
            : $response->getContent();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function test_draft_invoice_cannot_be_downloaded(): void
    {
        Storage::fake('local');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->draftInvoice($company);

        $this->getJson("/api/v1/invoices/{$invoice->id}/download")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only issued invoices can be downloaded. Issue the invoice first.');
    }

    private function draftInvoice(Company $company): MonthlyInvoice
    {
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 900,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        return MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-PDF-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-30',
            'due_date' => '2026-07-15',
            'subtotal_rent' => 900,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ])->fresh();
    }
}
