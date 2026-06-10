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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncomeStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_statement_shows_rental_revenue_from_issued_invoice(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 1250, 50, 25);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/income-statement?from=2026-06-01&to=2026-06-30');

        $response->assertOk()
            ->assertJsonPath('data.totals.gross_revenue', 1325)
            ->assertJsonPath('data.totals.total_expenses', 0)
            ->assertJsonPath('data.totals.net_income', 1325);

        $revenueCodes = collect($response->json('data.sections.revenue.rows'))->pluck('code')->all();

        $this->assertContains(Account::CODE_RENTAL_INCOME, $revenueCodes);
        $this->assertContains(Account::CODE_UTILITY_INCOME, $revenueCodes);
        $this->assertContains(Account::CODE_SERVICE_INCOME, $revenueCodes);
    }

    public function test_income_statement_export_returns_csv(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 1000);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/income-statement/export?from=2026-06-01&to=2026-06-30');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Net income', $content);
        $this->assertStringContainsString(Account::CODE_RENTAL_INCOME, $content);
    }

    public function test_income_statement_pdf_endpoint_returns_pdf_or_422(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 800);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/income-statement/export-pdf?from=2026-06-01&to=2026-06-30');

        if ($response->headers->get('content-type') === 'application/pdf') {
            $response->assertOk();
            $this->assertGreaterThan(100, strlen($response->getContent()));
        } else {
            $response->assertStatus(422);
        }
    }

    public function test_income_statement_filters_by_billing_month(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 900);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/income-statement?billing_year=2026&billing_month=6')
            ->assertOk()
            ->assertJsonPath('data.totals.gross_revenue', 900)
            ->assertJsonPath('data.period.billing_year', 2026)
            ->assertJsonPath('data.period.billing_month', 6);

        $this->getJson('/api/v1/income-statement?billing_year=2026&billing_month=5')
            ->assertOk()
            ->assertJsonPath('data.totals.gross_revenue', 0);
    }

    public function test_income_statement_isolated_to_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        $this->issueRentalInvoice($companyA, $userA, 500);

        Sanctum::actingAs($userB);

        $this->getJson('/api/v1/income-statement?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->assertJsonPath('data.totals.gross_revenue', 0)
            ->assertJsonCount(0, 'data.sections.revenue.rows');
    }

    private function issueRentalInvoice(
        Company $company,
        User $user,
        float $rent,
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

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-IS-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-30',
            'subtotal_rent' => $rent,
            'subtotal_utilities' => $utilities,
            'subtotal_services' => $services,
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
