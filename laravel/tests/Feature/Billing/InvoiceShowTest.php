<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_line_items_and_controls(): void
    {
        [$user, $invoice] = $this->seedInvoiceWithLines();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonPath('data.invoice_number', $invoice->invoice_number)
            ->assertJsonPath('data.controls.can_issue', true)
            ->assertJsonPath('data.controls.can_void', true)
            ->assertJsonCount(2, 'data.line_items')
            ->assertJsonPath('data.line_items.0.line_type', 'rent')
            ->assertJsonPath('data.line_items.1.line_type', 'electricity');
    }

    /**
     * @return array{0: User, 1: MonthlyInvoice}
     */
    protected function seedInvoiceWithLines(): array
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
            'invoice_number' => 'INV-SHOW-001',
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

        InvoiceLineItem::query()->create([
            'monthly_invoice_id' => $invoice->id,
            'line_type' => 'rent',
            'description' => 'Monthly rent',
            'quantity' => 1,
            'unit_price' => 1000,
            'amount' => 1000,
            'sort_order' => 0,
        ]);

        InvoiceLineItem::query()->create([
            'monthly_invoice_id' => $invoice->id,
            'line_type' => 'electricity',
            'description' => 'Electricity consumption',
            'quantity' => 50,
            'unit_price' => 1,
            'amount' => 50,
            'sort_order' => 1,
        ]);

        return [$user, $invoice];
    }
}
