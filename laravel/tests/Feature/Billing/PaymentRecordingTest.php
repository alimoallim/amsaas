<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_allocates_to_tenant_open_invoice(): void
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
            'invoice_number' => 'INV-TEST-001',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 1000,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenant->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertCreated();

        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEqualsWithDelta(500, (float) $invoice->paid_amount, 0.01);

        $this->assertDatabaseHas('payment_allocations', [
            'monthly_invoice_id' => $invoice->id,
        ]);
    }

    public function test_tenant_balance_returns_open_invoices_for_building(): void
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

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 650,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-TEST-002',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 650,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 100,
            'status' => 'partially_paid',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/payments/tenant-balance?' . http_build_query([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.open_balance', 550)
            ->assertJsonPath('data.invoice_count', 1)
            ->assertJsonPath('data.invoices.0.invoice_number', 'INV-TEST-002');
    }

    public function test_tenant_balance_includes_utilities_on_issued_invoice(): void
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

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 550,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-TEST-UTIL',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 550,
            'subtotal_utilities' => 37.50,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 550,
            'status' => 'partially_paid',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/payments/tenant-balance?'.http_build_query([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data.open_balance', 37.50)
            ->assertJsonPath('data.amounts.utilities_on_invoices', 37.50)
            ->assertJsonPath('data.invoices.0.subtotal_utilities', 37.50);
    }

    public function test_tenant_balance_surfaces_pending_approved_utilities(): void
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

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 550,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $model = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
        ]);

        Charge::query()->create([
            'id' => (string) str()->uuid(),
            'uuid' => (string) str()->uuid(),
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'rental_agreement_id' => $agreement->id,
            'charge_type_id' => $chargeType->id,
            'charge_model_id' => $model->id,
            'charge_number' => 'CHG-TEST-PEND',
            'category' => Charge::CATEGORY_UTILITY,
            'billing_strategy' => 'metered',
            'status' => Charge::STATUS_APPROVED,
            'currency' => 'USD',
            'quantity' => 10,
            'unit_rate' => 3.75,
            'subtotal_amount' => 37.50,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 37.50,
            'service_period_start' => now()->startOfMonth()->toDateString(),
            'service_period_end' => now()->endOfMonth()->toDateString(),
            'description' => 'Electricity',
            'charged_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/payments/tenant-balance?'.http_build_query([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'year' => now()->year,
            'month' => now()->month,
        ]))
            ->assertOk()
            ->assertJsonPath('data.open_balance', 0)
            ->assertJsonPath('data.amounts.pending_utilities', 37.50)
            ->assertJsonPath('data.total_due_including_pending', 37.50)
            ->assertJsonCount(1, 'data.pending_utilities');
    }

    public function test_overpayment_holds_tenant_credit(): void
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

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 100,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-TEST-003',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 100,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenant->id,
            'amount' => 150,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.allocated_amount', 100)
            ->assertJsonPath('data.unallocated_amount', 50);

        $this->assertStringContainsString('tenant credit', (string) $response->json('message'));
    }

    public function test_unallocated_credit_reapplies_when_invoice_balance_opens(): void
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

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 550,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-TEST-004',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 550,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 550,
            'status' => 'paid',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])->assertCreated()
            ->assertJsonPath('data.unallocated_amount', 100);

        $invoice->update([
            'subtotal_utilities' => 37.50,
            'paid_amount' => 550,
            'status' => 'partially_paid',
        ]);
        $invoice->refresh();

        app(\App\Services\PaymentService::class)->reapplyUnallocatedPayments($company->id, $tenant->id);

        $invoice->refresh();
        $this->assertEqualsWithDelta(587.50, (float) $invoice->paid_amount, 0.01);
        $this->assertEquals('paid', $invoice->status);
    }
}
