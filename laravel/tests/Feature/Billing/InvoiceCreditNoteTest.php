<?php

namespace Tests\Feature\Billing;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\InvoiceCreditNoteService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    private function issuedInvoice(Company $company): MonthlyInvoice
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

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-CN-'.uniqid(),
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
        ]);

        app(InvoiceService::class)->issue($invoice);

        return $invoice->fresh();
    }

    public function test_credit_note_reverses_journal_and_cancels_invoice(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->issuedInvoice($company);

        $credited = app(InvoiceCreditNoteService::class)->issue(
            $invoice,
            $user,
            'Billing correction',
        );

        $this->assertSame('cancelled', $credited->status);
        $this->assertNotNull($credited->credit_note_number);
        $this->assertSame('Billing correction', $credited->credit_note_reason);

        $reversal = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_INVOICE_CREDIT_NOTE)
            ->where('source_id', $invoice->id)
            ->with('lines.account')
            ->first();

        $this->assertNotNull($reversal);
        $ar = $reversal->lines->first(fn ($line) => $line->account?->code === Account::CODE_ACCOUNTS_RECEIVABLE);
        $this->assertSame('900.0000', (string) $ar?->credit_amount);
    }

    public function test_credit_note_api_endpoint(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->issuedInvoice($company);

        $response = $this->postJson("/api/v1/invoices/{$invoice->id}/credit-note", [
            'reason' => 'Duplicate billing run',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.credit_note_reason', 'Duplicate billing run');
    }
}
