<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\SaleAgreement;
use App\Models\SalePaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\Sales\SaleAgreementPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostingWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_transfer_posts_to_bank_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->rentalInvoice($company, 500);
        app(InvoiceService::class)->issue($invoice);

        $payment = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => Agreement::query()->find($invoice->contract_id)->tenant_id,
            'amount' => 200,
            'payment_date' => '2026-07-01',
            'payment_method' => 'bank_transfer',
        ]);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_PAYMENT_ALLOCATION)
            ->where('source_id', $payment->allocations->first()->id)
            ->with('lines.account')
            ->firstOrFail();

        $bank = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_BANK);
        $this->assertNotNull($bank);
        $this->assertSame('200.0000', (string) $bank->debit_amount);
    }

    public function test_sale_invoice_posts_to_sale_revenue_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => Apartment::factory()->create(['company_id' => $company->id])->id,
            'invoice_number' => 'INV-SALE-'.uniqid(),
            'contract_type' => 'sale',
            'contract_id' => (string) \Illuminate\Support\Str::uuid(),
            'billing_year' => 2026,
            'billing_month' => 7,
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'subtotal_installment' => 2500,
            'paid_amount' => 0,
            'status' => 'draft',
        ])->fresh();

        Sanctum::actingAs($user);
        app(InvoiceService::class)->issue($invoice);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_INVOICE_ISSUED)
            ->where('source_id', $invoice->id)
            ->with('lines.account')
            ->firstOrFail();

        $saleRevenue = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_SALE_INCOME);
        $this->assertNotNull($saleRevenue);
        $this->assertSame('2500.0000', (string) $saleRevenue->credit_amount);
    }

    public function test_sale_payment_posts_receipt_and_ar_reduction(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'tenant_id' => null,
            'status' => Agreement::STATUS_ACTIVE,
            'agreement_type' => Agreement::TYPE_SALE,
        ]);

        SaleAgreement::query()->create([
            'id' => $agreement->id,
            'sale_price' => 100000,
            'down_payment' => 0,
            'is_installment_sale' => false,
        ]);

        $result = app(SaleAgreementPostingService::class)->recordPayment($user, $agreement->id, [
            'amount' => 1500,
            'payment_date' => '2026-07-10',
            'payment_method' => 'cash',
        ]);

        $allocation = SalePaymentAllocation::query()
            ->where('payment_id', $result['payment']->id)
            ->firstOrFail();

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_SALE_PAYMENT_ALLOCATION)
            ->where('source_id', $allocation->id)
            ->with('lines.account')
            ->firstOrFail();

        $cash = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CASH);
        $ar = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_ACCOUNTS_RECEIVABLE);

        $this->assertNotNull($cash);
        $this->assertNotNull($ar);
        $this->assertSame('1500.0000', (string) $cash->debit_amount);
        $this->assertSame('1500.0000', (string) $ar->credit_amount);
    }

    public function test_receipt_account_override_posts_to_selected_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->rentalInvoice($company, 400);
        app(InvoiceService::class)->issue($invoice);

        $payment = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => Agreement::query()->find($invoice->contract_id)->tenant_id,
            'amount' => 200,
            'payment_date' => '2026-07-01',
            'payment_method' => 'bank_transfer',
            'receipt_account_code' => Account::CODE_CASH,
        ]);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_PAYMENT_ALLOCATION)
            ->where('source_id', $payment->allocations->first()->id)
            ->with('lines.account')
            ->firstOrFail();

        $cash = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CASH);
        $this->assertNotNull($cash);
        $this->assertSame('200.0000', (string) $cash->debit_amount);
        $this->assertSame(Account::CODE_CASH, $payment->receipt_account_code);
    }

    public function test_payment_show_includes_journal_entries(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $invoice = $this->rentalInvoice($company, 300);
        app(InvoiceService::class)->issue($invoice);

        $payment = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => Agreement::query()->find($invoice->contract_id)->tenant_id,
            'amount' => 300,
            'payment_date' => '2026-07-01',
            'payment_method' => 'cash',
        ]);

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $cashName = Account::query()
            ->where('company_id', $company->id)
            ->where('code', Account::CODE_CASH)
            ->value('name');

        $response->assertOk()
            ->assertJsonPath('data.posting.receipt_account_code', Account::CODE_CASH)
            ->assertJsonPath('data.posting.receipt_account_name', $cashName)
            ->assertJsonCount(1, 'data.journal_entries')
            ->assertJsonPath('data.journal_entries.0.lines.0.account_code', Account::CODE_CASH);
    }

    public function test_customer_deposit_posts_to_deposits_payable(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $payment = app(PaymentService::class)->recordBuyerPayment($user, [
            'buyer_id' => Buyer::factory()->create(['company_id' => $company->id])->id,
            'amount' => 750,
            'payment_date' => '2026-07-05',
            'payment_method' => 'mobile_money',
        ]);

        app(JournalEntryService::class)->postCustomerDeposit($payment, $user->id);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_CUSTOMER_DEPOSIT)
            ->where('source_id', $payment->id)
            ->with('lines.account')
            ->firstOrFail();

        $wallet = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_MOBILE_MONEY);
        $deposit = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CUSTOMER_DEPOSITS_PAYABLE);

        $this->assertNotNull($wallet);
        $this->assertNotNull($deposit);
        $this->assertSame('750.0000', (string) $wallet->debit_amount);
        $this->assertSame('750.0000', (string) $deposit->credit_amount);
    }

    private function rentalInvoice(Company $company, float $rent): MonthlyInvoice
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
            'invoice_number' => 'INV-PW-'.uniqid(),
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
    }
}
