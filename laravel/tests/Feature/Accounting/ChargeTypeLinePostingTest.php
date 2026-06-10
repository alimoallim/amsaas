<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\InvoiceLineItem;
use App\Models\JournalEntry;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargeTypeLinePostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_issue_splits_credits_by_charge_type_ledger_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $rentType = ChargeType::factory()->create([
            'company_id' => $company->id,
            'category' => ChargeType::CATEGORY_RENT,
            'ledger_account_code' => Account::CODE_RENTAL_INCOME,
        ]);

        $utilityType = ChargeType::factory()->create([
            'company_id' => $company->id,
            'category' => ChargeType::CATEGORY_UTILITY,
            'ledger_account_code' => Account::CODE_UTILITY_INCOME,
        ]);

        $serviceType = ChargeType::factory()->create([
            'company_id' => $company->id,
            'category' => ChargeType::CATEGORY_SERVICE,
            'ledger_account_code' => Account::CODE_SERVICE_INCOME,
        ]);

        $invoice = $this->rentalInvoice($company);

        InvoiceLineItem::query()->create([
            'monthly_invoice_id' => $invoice->id,
            'charge_type_id' => $rentType->id,
            'line_type' => 'rent',
            'description' => 'Monthly rent',
            'quantity' => 1,
            'unit_price' => 1000,
            'amount' => 1000,
            'sort_order' => 0,
        ]);

        InvoiceLineItem::query()->create([
            'monthly_invoice_id' => $invoice->id,
            'charge_type_id' => $utilityType->id,
            'line_type' => 'electricity',
            'description' => 'Electricity recovery',
            'quantity' => 1,
            'unit_price' => 75,
            'amount' => 75,
            'sort_order' => 1,
        ]);

        InvoiceLineItem::query()->create([
            'monthly_invoice_id' => $invoice->id,
            'charge_type_id' => $serviceType->id,
            'line_type' => 'service',
            'description' => 'Parking fee',
            'quantity' => 1,
            'unit_price' => 50,
            'amount' => 50,
            'sort_order' => 2,
        ]);

        $invoice->update([
            'subtotal_rent' => 1000,
            'subtotal_utilities' => 75,
            'subtotal_services' => 50,
        ]);
        $invoice->refresh();

        app(InvoiceService::class)->issue($invoice);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_INVOICE_ISSUED)
            ->where('source_id', $invoice->id)
            ->with('lines.account')
            ->firstOrFail();

        $rent = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_RENTAL_INCOME);
        $utility = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_UTILITY_INCOME);
        $service = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_SERVICE_INCOME);

        $this->assertNotNull($rent);
        $this->assertNotNull($utility);
        $this->assertNotNull($service);
        $this->assertSame('1000.0000', (string) $rent->credit_amount);
        $this->assertSame('75.0000', (string) $utility->credit_amount);
        $this->assertSame('50.0000', (string) $service->credit_amount);
        $this->assertSame('1125.0000', (string) $entry->total_credit);
    }

    public function test_custom_ledger_code_on_charge_type_overrides_category_default(): void
    {
        $company = Company::factory()->create();

        Account::factory()->create([
            'company_id' => $company->id,
            'code' => '4199',
            'name' => 'Amenity Income',
            'type' => Account::TYPE_REVENUE,
        ]);

        $customService = ChargeType::factory()->create([
            'company_id' => $company->id,
            'category' => ChargeType::CATEGORY_SERVICE,
            'ledger_account_code' => '4199',
        ]);

        $invoice = $this->rentalInvoice($company, rent: 0, utilities: 0, services: 200);

        InvoiceLineItem::query()->create([
            'monthly_invoice_id' => $invoice->id,
            'charge_type_id' => $customService->id,
            'line_type' => 'service',
            'description' => 'Custom amenity',
            'quantity' => 1,
            'unit_price' => 200,
            'amount' => 200,
            'sort_order' => 0,
        ]);

        app(InvoiceService::class)->issue($invoice);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_INVOICE_ISSUED)
            ->where('source_id', $invoice->id)
            ->with('lines.account')
            ->firstOrFail();

        $custom = $entry->lines->first(fn ($line) => $line->account?->code === '4199');

        $this->assertNotNull($custom);
        $this->assertSame('200.0000', (string) $custom->credit_amount);
    }

    public function test_invoice_without_line_items_falls_back_to_category_buckets(): void
    {
        $company = Company::factory()->create();

        $invoice = $this->rentalInvoice($company, rent: 800, utilities: 40, services: 0);

        app(InvoiceService::class)->issue($invoice);

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_INVOICE_ISSUED)
            ->where('source_id', $invoice->id)
            ->with('lines.account')
            ->firstOrFail();

        $rent = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_RENTAL_INCOME);
        $utility = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_UTILITY_INCOME);

        $this->assertNotNull($rent);
        $this->assertNotNull($utility);
        $this->assertSame('800.0000', (string) $rent->credit_amount);
        $this->assertSame('40.0000', (string) $utility->credit_amount);
    }

    private function rentalInvoice(
        Company $company,
        float $rent = 1000,
        float $utilities = 0,
        float $services = 0,
    ): MonthlyInvoice {
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
            'invoice_number' => 'INV-CT-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 7,
            'issue_date' => '2026-07-01',
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
}
