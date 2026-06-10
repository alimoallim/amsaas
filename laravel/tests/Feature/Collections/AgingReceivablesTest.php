<?php

namespace Tests\Feature\Collections;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Collections\AgingReceivablesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgingReceivablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_aging_buckets_outstanding_invoices_by_due_date(): void
    {
        [$user, $agreement, $apartment] = $this->seedLease();
        $asOf = Carbon::parse('2026-06-15');

        $this->createOpenInvoice($user->company_id, $apartment->id, $agreement->id, [
            'invoice_number' => 'INV-CURRENT',
            'due_date' => '2026-06-20',
            'subtotal_rent' => 100,
        ]);
        $this->createOpenInvoice($user->company_id, $apartment->id, $agreement->id, [
            'invoice_number' => 'INV-30',
            'due_date' => '2026-06-01',
            'subtotal_rent' => 200,
        ]);
        $this->createOpenInvoice($user->company_id, $apartment->id, $agreement->id, [
            'invoice_number' => 'INV-90PLUS',
            'due_date' => '2026-02-01',
            'subtotal_rent' => 300,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/reports/aging?'.http_build_query([
            'as_of' => $asOf->toDateString(),
            'group_by' => 'invoice',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.buckets.current.amount', 100)
            ->assertJsonPath('data.buckets.days_1_30.amount', 200)
            ->assertJsonPath('data.buckets.days_over_90.amount', 300)
            ->assertJsonPath('data.buckets.total.amount', 600)
            ->assertJsonCount(3, 'data.rows');
    }

    public function test_aging_groups_by_tenant(): void
    {
        [$user, $agreement, $apartment] = $this->seedLease();
        $asOf = Carbon::parse('2026-06-15');

        $this->createOpenInvoice($user->company_id, $apartment->id, $agreement->id, [
            'invoice_number' => 'INV-A',
            'due_date' => '2026-06-01',
            'subtotal_rent' => 150,
        ]);
        $this->createOpenInvoice($user->company_id, $apartment->id, $agreement->id, [
            'invoice_number' => 'INV-B',
            'due_date' => '2026-05-01',
            'subtotal_rent' => 250,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/reports/aging?'.http_build_query([
            'as_of' => $asOf->toDateString(),
            'group_by' => 'tenant',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.total_balance', 400)
            ->assertJsonPath('data.rows.0.invoice_count', 2);
    }

    public function test_aging_export_returns_csv(): void
    {
        [$user, $agreement, $apartment] = $this->seedLease();

        $this->createOpenInvoice($user->company_id, $apartment->id, $agreement->id, [
            'invoice_number' => 'INV-CSV-001',
            'due_date' => '2026-06-01',
            'subtotal_rent' => 500,
        ]);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/reports/aging/export?as_of=2026-06-15');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('INV-CSV-001', $response->getContent());
    }

    public function test_bucket_for_due_date_boundaries(): void
    {
        $service = app(AgingReceivablesService::class);
        $asOf = Carbon::parse('2026-06-15');

        $this->assertSame(
            AgingReceivablesService::BUCKET_CURRENT,
            $service->bucketForDueDate(Carbon::parse('2026-06-15'), $asOf)
        );
        $this->assertSame(
            AgingReceivablesService::BUCKET_DAYS_1_30,
            $service->bucketForDueDate(Carbon::parse('2026-06-01'), $asOf)
        );
        $this->assertSame(
            AgingReceivablesService::BUCKET_DAYS_31_60,
            $service->bucketForDueDate(Carbon::parse('2026-04-15'), $asOf)
        );
        $this->assertSame(
            AgingReceivablesService::BUCKET_DAYS_OVER_90,
            $service->bucketForDueDate(Carbon::parse('2026-02-01'), $asOf)
        );
    }

    /**
     * @return array{0: User, 1: Agreement, 2: Apartment}
     */
    protected function seedLease(): array
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
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        return [$user, $agreement, $apartment];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createOpenInvoice(
        string $companyId,
        string $apartmentId,
        string $agreementId,
        array $overrides = [],
    ): MonthlyInvoice {
        return MonthlyInvoice::query()->create(array_merge([
            'company_id' => $companyId,
            'apartment_id' => $apartmentId,
            'invoice_number' => 'INV-'.str()->random(6),
            'contract_type' => 'rental',
            'contract_id' => $agreementId,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-15',
            'subtotal_rent' => 100,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
        ], $overrides));
    }
}
