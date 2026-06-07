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
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChargeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_pending_charge(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $charge = Charge::factory()->create([
            'company_id' => $company->id,
            'status' => Charge::STATUS_PENDING,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/charges/{$charge->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status.value', Charge::STATUS_APPROVED);

        $this->assertEquals(Charge::STATUS_APPROVED, $charge->fresh()->status);
        $this->assertNotNull($charge->fresh()->approved_at);
    }

    public function test_reject_pending_charge_requires_reason(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $charge = Charge::factory()->create([
            'company_id' => $company->id,
            'status' => Charge::STATUS_PENDING,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/charges/{$charge->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_reject_pending_charge_cancels(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $charge = Charge::factory()->create([
            'company_id' => $company->id,
            'status' => Charge::STATUS_PENDING,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/charges/{$charge->id}/reject", [
            'reason' => 'Incorrect consumption band',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.value', Charge::STATUS_CANCELLED);
    }

    public function test_cannot_approve_already_approved_charge(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $charge = Charge::factory()->approved()->create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/charges/{$charge->id}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_approve_utility_syncs_to_existing_issued_invoice(): void
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
            'invoice_number' => 'INV-APPROVE-SYNC',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 550,
            'subtotal_utilities' => 0,
            'subtotal_services' => 540,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $model = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
        ]);

        $charge = Charge::query()->create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'rental_agreement_id' => $agreement->id,
            'charge_type_id' => $chargeType->id,
            'charge_model_id' => $model->id,
            'charge_number' => 'CHG-APPROVE-SYNC',
            'category' => Charge::CATEGORY_UTILITY,
            'billing_strategy' => 'metered',
            'status' => Charge::STATUS_PENDING,
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

        $this->postJson("/api/v1/charges/{$charge->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status.value', Charge::STATUS_INVOICED);

        $charge->refresh();
        $this->assertNotNull($charge->invoice_id);
        $this->assertSame(Charge::STATUS_INVOICED, $charge->status);

        $invoice = MonthlyInvoice::query()->where('invoice_number', 'INV-APPROVE-SYNC')->first();
        $this->assertEqualsWithDelta(37.50, (float) $invoice->subtotal_utilities, 0.01);
        $this->assertEqualsWithDelta(1127.50, (float) $invoice->balance_due, 0.01);

        $this->getJson('/api/v1/payments/tenant-balance?'.http_build_query([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data.open_balance', 1127.50)
            ->assertJsonPath('data.amounts.utilities_on_invoices', 37.50)
            ->assertJsonPath('data.amounts.pending_utilities', 0);
    }

    public function test_bulk_approve_only_pending_charges(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $pending = Charge::factory()->create(['company_id' => $company->id]);
        $approved = Charge::factory()->approved()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/charges/bulk-approve', [
            'charge_ids' => [$pending->id, $approved->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.approved', 1)
            ->assertJsonPath('data.skipped', 1);
    }
}
