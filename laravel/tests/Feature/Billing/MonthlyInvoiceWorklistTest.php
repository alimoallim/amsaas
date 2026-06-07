<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonthlyInvoiceWorklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_and_attention_list_for_period(): void
    {
        [$company, $user, $invoice] = $this->seedDraftInvoice();

        Sanctum::actingAs($user);

        $year = (int) $invoice->billing_year;
        $month = (int) $invoice->billing_month;

        $tenant = Agreement::find($invoice->contract_id)?->tenant;

        $this->getJson("/api/v1/invoices/summary?year={$year}&month={$month}")
            ->assertOk()
            ->assertJsonPath('data.counts.draft', 1)
            ->assertJsonPath('data.can_bulk_issue', true)
            ->assertJsonPath('data.amounts.open_balance', 0)
            ->assertJsonPath('data.amounts.draft_balance', 1050);

        $this->getJson("/api/v1/invoices?year={$year}&month={$month}&view=attention")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice_number', $invoice->invoice_number)
            ->assertJsonPath('data.0.controls.can_issue', true)
            ->assertJsonPath('data.0.tenant.display_name', $tenant->display_name);
    }

    public function test_open_balance_excludes_drafts_and_includes_issued(): void
    {
        [$company, $user, $draft] = $this->seedDraftInvoice();

        $apartment2 = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => Building::factory()->create(['company_id' => $company->id])->id,
        ]);
        $agreement2 = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment2->id,
            'tenant_id' => Tenant::factory()->create(['company_id' => $company->id])->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment2->id,
            'invoice_number' => 'INV-WL-002',
            'contract_type' => 'rental',
            'contract_id' => $agreement2->id,
            'billing_year' => $draft->billing_year,
            'billing_month' => $draft->billing_month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 500,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 100,
            'status' => 'partially_paid',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/invoices/summary?year={$draft->billing_year}&month={$draft->billing_month}")
            ->assertOk()
            ->assertJsonPath('data.amounts.draft_balance', 1050)
            ->assertJsonPath('data.amounts.open_balance', 400);
    }

    public function test_tenant_display_name_falls_back_to_first_and_last_name(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create([
            'company_id' => $company->id,
            'display_name' => '',
            'first_name' => 'Ali',
            'last_name' => 'Hassan',
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-WL-003',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 500,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/invoices?year='.$invoice->billing_year.'&month='.$invoice->billing_month.'&view=attention')
            ->assertOk()
            ->assertJsonPath('data.0.tenant.display_name', 'Ali Hassan');
    }

    public function test_bulk_issue_issues_drafts_for_period(): void
    {
        [$company, $user, $invoice] = $this->seedDraftInvoice();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/invoices/bulk-issue', [
            'year' => $invoice->billing_year,
            'month' => $invoice->billing_month,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.issued', 1);

        $invoice->refresh();
        $this->assertEquals('issued', $invoice->status);
    }

    /**
     * @return array{0: Company, 1: User, 2: MonthlyInvoice}
     */
    protected function seedDraftInvoice(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
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

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-WL-001',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 1000,
            'subtotal_utilities' => 50,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ]);

        return [$company, $user, $invoice];
    }
}
