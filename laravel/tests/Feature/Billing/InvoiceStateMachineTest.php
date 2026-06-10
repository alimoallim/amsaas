<?php

namespace Tests\Feature\Billing;

use App\Enums\MonthlyInvoiceStatus;
use App\Events\InvoiceIssued;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_invoice_status_helpers(): void
    {
        $this->assertTrue(MonthlyInvoiceStatus::Draft->isIssuable());
        $this->assertFalse(MonthlyInvoiceStatus::Issued->isIssuable());
        $this->assertTrue(MonthlyInvoiceStatus::Issued->isVoidable());
        $this->assertTrue(MonthlyInvoiceStatus::PartiallyPaid->isVoidable());
        $this->assertFalse(MonthlyInvoiceStatus::Paid->isVoidable());
        $this->assertFalse(MonthlyInvoiceStatus::Cancelled->isVoidable());
    }

    public function test_finalize_issues_draft_invoice_and_dispatches_event(): void
    {
        Event::fake([InvoiceIssued::class]);

        [$user, $invoice] = $this->seedDraftInvoice();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/finalize")
            ->assertOk()
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.controls.can_issue', false)
            ->assertJsonPath('data.controls.can_void', true)
            ->assertJsonPath('data.controls.can_edit', false);

        $invoice->refresh();
        $this->assertEquals('issued', $invoice->status);
        $this->assertNotNull($invoice->finalized_at);

        Event::assertDispatched(InvoiceIssued::class, fn (InvoiceIssued $event) => $event->invoice->id === $invoice->id);
    }

    public function test_finalize_rejects_non_draft_invoice(): void
    {
        [$user, $invoice] = $this->seedDraftInvoice('issued');

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/invoices/{$invoice->id}/finalize")
            ->assertStatus(422);
    }

    public function test_apply_payment_transitions_partially_paid_to_paid(): void
    {
        [$user, $invoice] = $this->seedDraftInvoice('issued');

        $service = app(InvoiceService::class);

        $service->applyPayment($invoice, 400);
        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEqualsWithDelta(400, (float) $invoice->paid_amount, 0.01);

        $service->applyPayment($invoice, 600);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEqualsWithDelta(1000, (float) $invoice->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $invoice->balance_due, 0.01);
    }

    public function test_apply_payment_rejects_paid_invoice(): void
    {
        [$user, $invoice] = $this->seedDraftInvoice('paid');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(InvoiceService::class)->applyPayment($invoice, 50);
    }

    /**
     * @return array{0: User, 1: MonthlyInvoice}
     */
    protected function seedDraftInvoice(string $status = 'draft'): array
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

        $paidAmount = match ($status) {
            'partially_paid' => 200,
            'paid' => 1000,
            default => 0,
        };

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-SM-'.strtoupper(substr(md5((string) microtime()), 0, 6)),
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
            'status' => $status === 'issued' || $status === 'partially_paid' || $status === 'paid'
                ? $status
                : 'draft',
        ]);

        if ($status === 'issued') {
            $invoice->update([
                'status' => 'issued',
                'finalized_at' => now(),
            ]);
            $invoice->refresh();
        }

        return [$user, $invoice];
    }
}
