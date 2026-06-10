<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\DepositApplication;
use App\Models\JournalEntry;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\RentalDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RentalDepositPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_deposit_posts_to_customer_deposits_liability(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        [$agreement, $tenant] = $this->rentalAgreement($company, securityDeposit: 1000);

        $payment = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'agreement_id' => $agreement->id,
            'payment_purpose' => Payment::PURPOSE_SECURITY_DEPOSIT,
            'amount' => 500,
            'payment_date' => '2026-07-01',
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertSame(Payment::PURPOSE_SECURITY_DEPOSIT, $payment->payment_purpose);
        $this->assertCount(0, $payment->allocations);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_RENTAL_SECURITY_DEPOSIT)
            ->where('source_id', $payment->id)
            ->with('lines.account')
            ->firstOrFail();

        $bank = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_BANK);
        $deposit = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CUSTOMER_DEPOSITS_PAYABLE);

        $this->assertNotNull($bank);
        $this->assertNotNull($deposit);
        $this->assertSame('500.0000', (string) $bank->debit_amount);
        $this->assertSame('500.0000', (string) $deposit->credit_amount);
    }

    public function test_security_deposit_does_not_allocate_to_rent_invoices(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        [$agreement, $tenant, $invoice] = $this->rentalAgreementWithInvoice($company, 300);
        app(InvoiceService::class)->issue($invoice);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'agreement_id' => $agreement->id,
            'payment_purpose' => Payment::PURPOSE_SECURITY_DEPOSIT,
            'amount' => 300,
            'payment_date' => '2026-07-01',
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertSame(0.0, (float) $invoice->paid_amount);
        $this->assertSame('issued', $invoice->status);
    }

    public function test_deposit_refund_reverses_liability_to_receipt(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        [$agreement, $tenant] = $this->rentalAgreement($company, securityDeposit: 500);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'agreement_id' => $agreement->id,
            'payment_purpose' => Payment::PURPOSE_SECURITY_DEPOSIT,
            'amount' => 500,
            'payment_date' => '2026-07-01',
            'payment_method' => 'cash',
        ]);

        $refund = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'agreement_id' => $agreement->id,
            'payment_purpose' => Payment::PURPOSE_DEPOSIT_REFUND,
            'amount' => 200,
            'payment_date' => '2026-08-01',
            'payment_method' => 'cash',
        ]);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_RENTAL_DEPOSIT_REFUND)
            ->where('source_id', $refund->id)
            ->with('lines.account')
            ->firstOrFail();

        $deposit = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CUSTOMER_DEPOSITS_PAYABLE);
        $cash = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CASH);

        $this->assertNotNull($deposit);
        $this->assertNotNull($cash);
        $this->assertSame('200.0000', (string) $deposit->debit_amount);
        $this->assertSame('200.0000', (string) $cash->credit_amount);
    }

    public function test_deposit_application_posts_liability_to_ar_and_pays_invoice(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        [$agreement, $tenant, $invoice] = $this->rentalAgreementWithInvoice($company, 400);
        app(InvoiceService::class)->issue($invoice);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'agreement_id' => $agreement->id,
            'payment_purpose' => Payment::PURPOSE_SECURITY_DEPOSIT,
            'amount' => 400,
            'payment_date' => '2026-07-01',
            'payment_method' => 'bank_transfer',
        ]);

        $application = app(RentalDepositService::class)->applyToInvoice($user, $agreement->id, [
            'monthly_invoice_id' => $invoice->id,
            'amount' => 250,
        ]);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_RENTAL_DEPOSIT_APPLICATION)
            ->where('source_id', $application->id)
            ->with('lines.account')
            ->firstOrFail();

        $deposit = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CUSTOMER_DEPOSITS_PAYABLE);
        $ar = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_ACCOUNTS_RECEIVABLE);

        $this->assertNotNull($deposit);
        $this->assertNotNull($ar);
        $this->assertSame('250.0000', (string) $deposit->debit_amount);
        $this->assertSame('250.0000', (string) $ar->credit_amount);

        $invoice->refresh();
        $this->assertSame(250.0, (float) $invoice->paid_amount);
        $this->assertSame('partially_paid', $invoice->status);

        $summary = app(RentalDepositService::class)->summary($company->id, $agreement->id);
        $this->assertSame(400.0, $summary['received']);
        $this->assertSame(250.0, $summary['applied']);
        $this->assertSame(150.0, $summary['available']);
    }

    public function test_rental_agreement_show_includes_deposit_ledger(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        [$agreement, $tenant] = $this->rentalAgreement($company, securityDeposit: 800);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'agreement_id' => $agreement->id,
            'payment_purpose' => Payment::PURPOSE_SECURITY_DEPOSIT,
            'amount' => 600,
            'payment_date' => '2026-07-01',
            'payment_method' => 'cash',
        ]);

        $response = $this->getJson("/api/v1/rental-agreements/{$agreement->id}");

        $response->assertOk()
            ->assertJsonPath('data.financials.deposit_ledger.required', 800)
            ->assertJsonPath('data.financials.deposit_ledger.received', 600)
            ->assertJsonPath('data.financials.deposit_ledger.available', 600);
    }

    /**
     * @return array{0: Agreement, 1: Tenant}
     */
    private function rentalAgreement(Company $company, float $securityDeposit = 500): array
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
            'monthly_rent' => 1000,
            'security_deposit' => $securityDeposit,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        return [$agreement, $tenant];
    }

    /**
     * @return array{0: Agreement, 1: Tenant, 2: MonthlyInvoice}
     */
    private function rentalAgreementWithInvoice(Company $company, float $rent): array
    {
        [$agreement, $tenant] = $this->rentalAgreement($company);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $agreement->apartment_id,
            'invoice_number' => 'INV-RD-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 7,
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'subtotal_rent' => $rent,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ])->fresh();

        return [$agreement, $tenant, $invoice];
    }
}
