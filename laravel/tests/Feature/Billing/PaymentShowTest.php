<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_receipt_allocations_and_controls(): void
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
            'monthly_rent' => 800,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-SHOW-PAY',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 800,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenant->id,
            'amount' => 300,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-001',
        ]);

        $create->assertCreated();
        $paymentId = $create->json('data.id');

        $this->getJson("/api/v1/payments/{$paymentId}")
            ->assertOk()
            ->assertJsonPath('data.controls.can_view_receipt', true)
            ->assertJsonPath('data.allocated_amount', 300)
            ->assertJsonPath('data.unallocated_amount', 0)
            ->assertJsonPath('data.reference_number', 'REF-001')
            ->assertJsonCount(1, 'data.allocations')
            ->assertJsonPath('data.allocations.0.invoice_number', 'INV-SHOW-PAY');
    }

    public function test_show_returns_404_for_other_company_payment(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $tenantB = Tenant::factory()->create(['company_id' => $companyB->id]);

        Sanctum::actingAs($userB);

        $paymentId = $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenantB->id,
            'amount' => 50,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->json('data.id');

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/payments/{$paymentId}")
            ->assertNotFound();
    }

    public function test_index_returns_flat_payment_array(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/payments', [
            'tenant_id' => $tenant->id,
            'amount' => 25,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertCreated();

        $response = $this->getJson('/api/v1/payments');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('receipt_number', $data[0]);
        $this->assertArrayNotHasKey('data', $data);
    }
}
