<?php

namespace Tests\Feature\Accounting;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_audit_lists_payment_and_journal_events(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $invoice = $this->issueRentalInvoice($company, $user, 800);

        app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => Agreement::query()->find($invoice->contract_id)->tenant_id,
            'amount' => 300,
            'payment_date' => '2026-06-20',
            'payment_method' => 'cash',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/financial-audit?from=2026-06-01&to=2026-06-30');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['occurred_at', 'action', 'entity_type', 'summary', 'source'],
                ],
                'meta' => ['total', 'current_page'],
            ]);

        $entityTypes = collect($response->json('data'))->pluck('entity_type')->unique()->values()->all();

        $this->assertContains('payment', $entityTypes);
        $this->assertContains('journal_entry', $entityTypes);
    }

    public function test_financial_audit_filters_by_entity_type(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 500);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/financial-audit?from=2026-06-01&to=2026-06-30&entity_type=journal_entry');

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame('journal_entry', $row['entity_type']);
        }
    }

    public function test_financial_audit_export_returns_csv(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->issueRentalInvoice($company, $user, 500);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/financial-audit/export?from=2026-06-01&to=2026-06-30');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Summary', $response->streamedContent());
    }

    public function test_financial_audit_isolated_to_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        $this->issueRentalInvoice($companyA, $userA, 500);

        Sanctum::actingAs($userB);

        $response = $this->getJson('/api/v1/financial-audit?from=2026-06-01&to=2026-06-30');

        $response->assertOk();

        $entityTypes = collect($response->json('data'))->pluck('entity_type')->unique()->values()->all();

        $this->assertNotContains('payment', $entityTypes);
        $this->assertNotContains('monthly_invoice', $entityTypes);
        $this->assertNotContains('journal_entry', $entityTypes);
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
            'invoice_number' => 'INV-FA-'.uniqid(),
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
