<?php

namespace Tests\Feature\Payments;

use App\Models\Account;
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
use App\Services\PaymentRefundService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentRefundTest extends TestCase
{
    use RefreshDatabase;

    private function paidInvoiceContext(Company $company): array
    {
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);

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
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-RF-'.uniqid(),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-30',
            'due_date' => '2026-07-15',
            'subtotal_rent' => 500,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ]);

        app(InvoiceService::class)->issue($invoice);

        $payment = app(PaymentService::class)->recordPayment($user, [
            'tenant_id' => $tenant->id,
            'amount' => 500,
            'payment_date' => '2026-07-01',
            'payment_method' => 'cash',
        ]);

        return [$user, $invoice->fresh(), $payment->fresh(['allocations'])];
    }

    public function test_refund_reverses_allocation_and_restores_invoice_balance(): void
    {
        $company = Company::factory()->create();
        [$user, $invoice, $payment] = $this->paidInvoiceContext($company);

        $this->assertSame('paid', $invoice->status);

        $allocationId = $payment->allocations->first()->id;

        $refunded = app(PaymentRefundService::class)->refund(
            $user,
            $payment,
            500,
            'Customer dispute',
        );

        $this->assertSame('refunded', $refunded->status);
        $this->assertSame(500.0, (float) $refunded->refunded_amount);

        $invoice->refresh();
        $this->assertSame(0.0, (float) $invoice->paid_amount);
        $this->assertContains($invoice->status, ['issued', 'overdue']);

        $reversal = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_PAYMENT_ALLOCATION_REVERSAL)
            ->where('source_id', $allocationId)
            ->with('lines.account')
            ->first();

        $this->assertNotNull($reversal);
        $cash = $reversal->lines->first(fn ($line) => $line->account?->code === Account::CODE_CASH);
        $this->assertSame('500.0000', (string) $cash?->credit_amount);
    }

    public function test_refund_api_endpoint(): void
    {
        $company = Company::factory()->create();
        [$user, , $payment] = $this->paidInvoiceContext($company);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/refund", [
            'amount' => 500,
            'reason' => 'Posted in error',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.refunded_amount', 500);
    }
}
