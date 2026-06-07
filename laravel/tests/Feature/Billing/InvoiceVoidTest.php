<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Charge;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_void_draft_invoice(): void
    {
        [$user, $invoice] = $this->seedIssuedInvoice('draft');

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/void", [
            'reason' => 'Created in error for wrong period.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.void_reason', 'Created in error for wrong period.');

        $this->assertEquals('cancelled', $invoice->fresh()->status);
    }

    public function test_void_issued_invoice_releases_linked_charge(): void
    {
        [$user, $invoice] = $this->seedIssuedInvoice('issued');

        $charge = Charge::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'charge_number' => 'CH-VOID-1',
            'company_id' => $invoice->company_id,
            'category' => Charge::CATEGORY_UTILITY,
            'billing_strategy' => 'metered',
            'status' => Charge::STATUS_INVOICED,
            'currency' => 'USD',
            'total_amount' => 50,
            'subtotal_amount' => 50,
            'invoice_id' => $invoice->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/void", [
            'reason' => 'Tenant dispute — re-bill next cycle.',
        ])->assertOk();

        $charge->refresh();
        $this->assertNull($charge->invoice_id);
        $this->assertEquals(Charge::STATUS_APPROVED, $charge->status);
    }

    public function test_void_reverses_payment_allocations(): void
    {
        [$user, $invoice, $tenant] = $this->seedIssuedInvoiceWithTenant('partially_paid', 200);

        $payment = Payment::query()->create([
            'company_id' => $invoice->company_id,
            'tenant_id' => $tenant->id,
            'receipt_number' => 'RCP-TEST-1',
            'amount' => 200,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'recorded_by' => $user->id,
        ]);

        PaymentAllocation::query()->create([
            'payment_id' => $payment->id,
            'monthly_invoice_id' => $invoice->id,
            'amount_allocated' => 200,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/void", [
            'reason' => 'Invoice issued to wrong tenant.',
        ])->assertOk();

        $this->assertDatabaseMissing('payment_allocations', [
            'monthly_invoice_id' => $invoice->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals('cancelled', $invoice->status);
    }

    public function test_void_requires_reason(): void
    {
        [$user, $invoice] = $this->seedIssuedInvoice('issued');

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/void", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_paid_invoice_cannot_be_voided(): void
    {
        [$user, $invoice] = $this->seedIssuedInvoice('paid');

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/void", [
            'reason' => 'Should not work.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @return array{0: User, 1: MonthlyInvoice}
     */
    protected function seedIssuedInvoice(string $status): array
    {
        [$user, $invoice] = $this->seedIssuedInvoiceWithTenant($status, 0);

        return [$user, $invoice];
    }

    /**
     * @return array{0: User, 1: MonthlyInvoice, 2: Tenant}
     */
    protected function seedIssuedInvoiceWithTenant(string $status, float $paidAmount): array
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
            'invoice_number' => 'INV-VOID-'.Str::upper(Str::random(4)),
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
            'paid_amount' => $paidAmount,
            'status' => $status,
        ]);

        return [$user, $invoice, $tenant];
    }
}
