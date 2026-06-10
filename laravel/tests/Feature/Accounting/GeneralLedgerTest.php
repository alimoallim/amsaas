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

class GeneralLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function postInvoiceAndPayment(Company $company, User $user): array
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
            'invoice_number' => 'INV-GL-'.uniqid(),
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

        $ar = Account::query()
            ->where('company_id', $company->id)
            ->where('code', Account::CODE_ACCOUNTS_RECEIVABLE)
            ->firstOrFail();

        return [$ar, $tenant];
    }

    public function test_ledger_returns_running_balance_for_ar_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        [$ar] = $this->postInvoiceAndPayment($company, $user);

        $response = $this->getJson("/api/v1/accounts/{$ar->id}/ledger?from=2026-06-01&to=2026-06-30");

        $response->assertOk()
            ->assertJsonPath('data.account.code', Account::CODE_ACCOUNTS_RECEIVABLE)
            ->assertJsonPath('data.summary.opening_balance', 0)
            ->assertJsonPath('data.summary.closing_balance', 600)
            ->assertJsonCount(2, 'data.rows');

        $rows = $response->json('data.rows');
        $this->assertEqualsWithDelta(1000, (float) $rows[0]['debit_amount'], 0.01);
        $this->assertEqualsWithDelta(1000, (float) $rows[0]['running_balance'], 0.01);
        $this->assertEqualsWithDelta(400, (float) $rows[1]['credit_amount'], 0.01);
        $this->assertEqualsWithDelta(600, (float) $rows[1]['running_balance'], 0.01);
    }

    public function test_ledger_export_returns_csv(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        [$ar] = $this->postInvoiceAndPayment($company, $user);

        $response = $this->get("/api/v1/accounts/{$ar->id}/ledger/export?from=2026-06-01&to=2026-06-30");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Opening balance', $content);
        $this->assertStringContainsString('Running balance', $content);
    }

    public function test_ledger_isolated_to_own_company_account(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $accountB = Account::query()
            ->where('company_id', $companyB->id)
            ->where('code', Account::CODE_ACCOUNTS_RECEIVABLE)
            ->firstOrFail();

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/accounts/{$accountB->id}/ledger")
            ->assertNotFound();
    }
}
