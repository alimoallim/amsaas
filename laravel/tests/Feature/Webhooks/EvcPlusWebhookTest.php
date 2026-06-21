<?php

namespace Tests\Feature\Webhooks;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvcPlusWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'evc.enabled' => true,
            'evc.webhook_secret' => 'test-secret',
        ]);
    }

    private function issuedInvoice(): MonthlyInvoice
    {
        $company = Company::factory()->create();
        User::factory()->create(['company_id' => $company->id]);

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
            'monthly_rent' => 750,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-EVC-001',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-30',
            'due_date' => '2026-07-15',
            'subtotal_rent' => 750,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
        ]);

        app(InvoiceService::class)->issue($invoice);

        return $invoice->fresh();
    }

    private function postSignedWebhook(array $payload)
    {
        $body = json_encode($payload);

        return $this->call(
            'POST',
            '/api/v1/webhooks/payments/evc-plus',
            [],
            [],
            [],
            [
                'HTTP_X_EVC_SIGNATURE' => hash_hmac('sha256', $body, 'test-secret'),
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $body,
        );
    }

    public function test_evc_webhook_records_payment_and_is_idempotent(): void
    {
        $invoice = $this->issuedInvoice();

        $payload = [
            'transaction_id' => 'EVC-TX-1001',
            'invoice_number' => $invoice->invoice_number,
            'amount' => 750,
            'paid_at' => '2026-07-02',
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertCreated()
            ->assertJsonPath('status', 'processed');

        $paymentId = $response->json('payment_id');
        $this->assertNotNull($paymentId);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'reference_number' => 'EVC-TX-1001',
            'payment_method' => 'mobile_money',
        ]);

        $duplicate = $this->postSignedWebhook($payload);

        $duplicate->assertOk()
            ->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, Payment::query()->where('reference_number', 'EVC-TX-1001')->count());
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'EVC-TX-1001')->count());
    }

    public function test_evc_webhook_rejects_invalid_signature(): void
    {
        $invoice = $this->issuedInvoice();

        $payload = [
            'transaction_id' => 'EVC-TX-BAD',
            'invoice_number' => $invoice->invoice_number,
            'amount' => 100,
        ];

        $body = json_encode($payload);
        $this->call(
            'POST',
            '/api/v1/webhooks/payments/evc-plus',
            [],
            [],
            [],
            [
                'HTTP_X_EVC_SIGNATURE' => 'invalid',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $body,
        )->assertUnauthorized();
    }
}
