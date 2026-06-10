<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriodClose;
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

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function postInvoiceAndPayment(Company $company, User $user): void
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
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-TB-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-30',
            'subtotal_rent' => 1000,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ])->fresh();

        Sanctum::actingAs($user);
        app(InvoiceService::class)->issue($invoice);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'amount' => 400,
            'payment_date' => '2026-06-20',
            'payment_method' => 'cash',
        ]);
    }

    public function test_trial_balance_balances_after_invoice_and_payment(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->postInvoiceAndPayment($company, $user);

        $response = $this->getJson('/api/v1/trial-balance?from=2026-06-01&to=2026-06-30');

        $response->assertOk()
            ->assertJsonPath('data.totals.balanced', true)
            ->assertJsonPath('data.totals.variance', 0)
            ->assertJsonPath('data.totals.activity_debit', 1400)
            ->assertJsonPath('data.totals.activity_credit', 1400)
            ->assertJsonPath('data.period_close.is_closed', false)
            ->assertJsonPath('data.controls.can_close_period', true);

        $rows = collect($response->json('data.rows'))->keyBy('code');

        $this->assertEqualsWithDelta(400, (float) $rows[Account::CODE_CASH]['balance_debit'], 0.01);
        $this->assertEqualsWithDelta(600, (float) $rows[Account::CODE_ACCOUNTS_RECEIVABLE]['balance_debit'], 0.01);
        $this->assertEqualsWithDelta(1000, (float) $rows[Account::CODE_RENTAL_INCOME]['balance_credit'], 0.01);
    }

    public function test_trial_balance_export_returns_csv(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->postInvoiceAndPayment($company, $user);

        $response = $this->get('/api/v1/trial-balance/export?from=2026-06-01&to=2026-06-30');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Balance debit', $content);
        $this->assertStringContainsString('TOTALS', $content);
        $this->assertStringContainsString(Account::CODE_ACCOUNTS_RECEIVABLE, $content);
    }

    public function test_close_period_records_balanced_month(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->postInvoiceAndPayment($company, $user);

        $response = $this->postJson('/api/v1/trial-balance/close-period', [
            'fiscal_year' => 2026,
            'fiscal_month' => 6,
            'notes' => 'June close',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.period_close.is_closed', true)
            ->assertJsonPath('data.period_close.fiscal_year', 2026)
            ->assertJsonPath('data.period_close.fiscal_month', 6)
            ->assertJsonPath('data.report.period_close.is_closed', true);

        $this->assertDatabaseHas('accounting_period_closes', [
            'company_id' => $company->id,
            'fiscal_year' => 2026,
            'fiscal_month' => 6,
            'trial_balance_balanced' => true,
            'closed_by' => $user->id,
        ]);
    }

    public function test_close_period_rejects_already_closed_month(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->postInvoiceAndPayment($company, $user);

        AccountingPeriodClose::query()->create([
            'company_id' => $company->id,
            'fiscal_year' => 2026,
            'fiscal_month' => 6,
            'trial_balance_balanced' => true,
            'total_debits' => 1000,
            'total_credits' => 1000,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $this->postJson('/api/v1/trial-balance/close-period', [
            'fiscal_year' => 2026,
            'fiscal_month' => 6,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PERIOD_ALREADY_CLOSED');
    }

    public function test_trial_balance_isolated_to_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        $this->postInvoiceAndPayment($companyA, $userA);

        Sanctum::actingAs($userB);

        $response = $this->getJson('/api/v1/trial-balance?from=2026-06-01&to=2026-06-30');

        $response->assertOk()
            ->assertJsonPath('data.totals.activity_debit', 0)
            ->assertJsonPath('data.totals.activity_credit', 0)
            ->assertJsonCount(0, 'data.rows');
    }
}
