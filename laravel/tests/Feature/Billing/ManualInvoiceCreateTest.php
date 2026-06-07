<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManualInvoiceCreateTest extends TestCase
{
    use RefreshDatabase;

    private function seedActiveLease(): array
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
            'monthly_rent' => 500,
            'security_deposit' => 0,
            'payment_due_day' => 10,
            'billing_cycle' => 'monthly',
        ]);

        return compact('company', 'user', 'apartment', 'agreement');
    }

    public function test_manual_invoice_creates_draft_with_line_items(): void
    {
        ['user' => $user, 'apartment' => $apartment, 'agreement' => $agreement] = $this->seedActiveLease();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/invoices', [
            'apartment_id' => $apartment->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-15',
            'discount_amount' => 25,
            'notes' => 'Manual adjustment',
            'line_items' => [
                [
                    'description' => 'June rent',
                    'line_type' => 'rent',
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
                [
                    'description' => 'Water consumption',
                    'line_type' => 'water',
                    'quantity' => 12.5,
                    'unit_price' => 2.4,
                ],
                [
                    'description' => 'Parking fee',
                    'line_type' => 'service',
                    'quantity' => 1,
                    'unit_price' => 50,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal_rent', fn ($v) => (float) $v === 500.0)
            ->assertJsonPath('data.subtotal_utilities', fn ($v) => (float) $v === 30.0)
            ->assertJsonPath('data.subtotal_services', fn ($v) => (float) $v === 50.0)
            ->assertJsonPath('data.discount_amount', fn ($v) => (float) $v === 25.0)
            ->assertJsonPath('data.total_amount', fn ($v) => (float) $v === 555.0)
            ->assertJsonCount(3, 'data.line_items');

        $this->assertDatabaseHas('monthly_invoices', [
            'apartment_id' => $apartment->id,
            'contract_id' => $agreement->id,
            'status' => 'draft',
            'notes' => 'Manual adjustment',
        ]);

        $this->assertDatabaseCount('invoice_line_items', 3);
    }

    public function test_manual_invoice_requires_at_least_one_line_item(): void
    {
        ['user' => $user, 'apartment' => $apartment] = $this->seedActiveLease();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/invoices', [
            'apartment_id' => $apartment->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'line_items' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['line_items']);
    }

    public function test_manual_invoice_rejects_zero_unit_price(): void
    {
        ['user' => $user, 'apartment' => $apartment] = $this->seedActiveLease();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/invoices', [
            'apartment_id' => $apartment->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'line_items' => [
                [
                    'description' => 'Bad line',
                    'line_type' => 'rent',
                    'quantity' => 1,
                    'unit_price' => 0,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['line_items.0.unit_price']);
    }
}
