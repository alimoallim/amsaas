<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BalanceSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_sheet_balances_after_invoice_and_partial_payment(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $invoice = $this->issueRentalInvoice($company, $user, 1000);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => Agreement::query()->find($invoice->contract_id)->tenant_id,
            'amount' => 400,
            'payment_date' => '2026-06-20',
            'payment_method' => 'cash',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/balance-sheet?as_of=2026-06-30');

        $response->assertOk()
            ->assertJsonPath('data.totals.balanced', true)
            ->assertJsonPath('data.totals.variance', 0);

        $assets = collect($response->json('data.sections.assets.rows'))->keyBy('code');
        $equity = collect($response->json('data.sections.equity.rows'))->keyBy('code');

        $this->assertEqualsWithDelta(400, (float) $assets[Account::CODE_CASH]['balance'], 0.01);
        $this->assertEqualsWithDelta(600, (float) $assets[Account::CODE_ACCOUNTS_RECEIVABLE]['balance'], 0.01);
        $this->assertEqualsWithDelta(1000, (float) $equity['3900']['balance'], 0.01);
    }

    public function test_balance_sheet_export_returns_csv(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 500);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/balance-sheet/export?as_of=2026-06-30');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Liabilities + Equity', $content);
        $this->assertStringContainsString(Account::CODE_ACCOUNTS_RECEIVABLE, $content);
    }

    public function test_balance_sheet_isolated_to_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        $this->issueRentalInvoice($companyA, $userA, 750);

        Sanctum::actingAs($userB);

        $this->getJson('/api/v1/balance-sheet?as_of=2026-06-30')
            ->assertOk()
            ->assertJsonPath('data.totals.assets', 0)
            ->assertJsonCount(0, 'data.sections.assets.rows');
    }

    private function issueRentalInvoice(Company $company, User $user, float $rent): MonthlyInvoice
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

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-BS-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-30',
            'subtotal_rent' => $rent,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ])->fresh();

        Sanctum::actingAs($user);
        app(InvoiceService::class)->issue($invoice);

        return $invoice;
    }
}
