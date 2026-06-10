<?php

namespace Tests\Feature\Accounting;

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
use App\Services\Accounting\JournalEntryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JournalPostingTest extends TestCase
{
    use RefreshDatabase;

    private function issuedInvoice(Company $company, float $rent = 1250, float $utilities = 0, float $services = 0): MonthlyInvoice
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
            'monthly_rent' => $rent,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        return MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-JE-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-30',
            'due_date' => '2026-07-15',
            'subtotal_rent' => $rent,
            'subtotal_utilities' => $utilities,
            'subtotal_services' => $services,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ])->fresh();
    }

    public function test_invoice_issue_posts_balanced_journal_entry(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->issuedInvoice($company, 1250, 50, 25);

        app(InvoiceService::class)->issue($invoice);

        $entry = JournalEntry::query()
            ->where('company_id', $company->id)
            ->where('source_type', JournalEntry::SOURCE_INVOICE_ISSUED)
            ->where('source_id', $invoice->id)
            ->with('lines.account')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('1325.0000', (string) $entry->total_debit);
        $this->assertSame('1325.0000', (string) $entry->total_credit);
        $this->assertSame('2026-06-30', $entry->entry_date->toDateString());

        $ar = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_ACCOUNTS_RECEIVABLE);
        $this->assertNotNull($ar);
        $this->assertSame('1325.0000', (string) $ar->debit_amount);
    }

    public function test_invoice_journal_is_idempotent(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->issuedInvoice($company, 500);

        $service = app(JournalEntryService::class);
        $first = $service->postInvoiceIssued($invoice);
        $second = $service->postInvoiceIssued($invoice);

        $this->assertSame($first?->id, $second?->id);
        $this->assertDatabaseCount('journal_entries', 1);
    }

    public function test_payment_allocation_posts_cash_and_ar_entries(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->issuedInvoice($company, 1000);
        app(InvoiceService::class)->issue($invoice);

        $payment = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => Agreement::query()->find($invoice->contract_id)->tenant_id,
            'amount' => 400,
            'payment_date' => '2026-07-01',
            'payment_method' => 'cash',
        ]);

        $allocation = $payment->allocations->first();
        $this->assertNotNull($allocation);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_PAYMENT_ALLOCATION)
            ->where('source_id', $allocation->id)
            ->with('lines.account')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('400.0000', (string) $entry->total_debit);

        $cash = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CASH);
        $ar = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_ACCOUNTS_RECEIVABLE);

        $this->assertSame('400.0000', (string) $cash->debit_amount);
        $this->assertSame('400.0000', (string) $ar->credit_amount);
    }
}
