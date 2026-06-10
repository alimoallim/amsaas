<?php

namespace Tests\Feature\Sales;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\Company;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Sales\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalePaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    private function activePaymentPlanContract(
        Company $company,
        User $user,
        float $salePrice = 200000,
        float $downPayment = 50000,
        int $years = 5,
    ): string {
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'market_sale_price' => $salePrice,
            'currency' => 'USD',
        ]);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        $reservation = app(ReservationService::class)->create($user, [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 10000,
            'record_deposit' => true,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => $salePrice,
            'down_payment' => $downPayment,
            'is_payment_plan' => true,
            'plan_duration_years' => $years,
            'contract_date' => '2026-01-15',
            'execute' => true,
        ])->assertCreated();

        return $response->json('data.id');
    }

    public function test_payment_plan_contract_sets_term_and_financed_amount(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->activePaymentPlanContract($company, $user, 250000, 50000, 5);

        $this->getJson("/api/v1/sale-agreements/{$id}")
            ->assertOk()
            ->assertJsonPath('data.payment_plan.mode', 'payment_plan')
            ->assertJsonPath('data.financials.financed_amount', 200000)
            ->assertJsonPath('data.financials.progress_percent', 0)
            ->assertJsonPath('data.payment_plan.plan_duration_years', 5)
            ->assertJsonPath('data.dates.start_date', '2026-01-15')
            ->assertJsonPath('data.dates.end_date', '2031-01-15')
            ->assertJsonPath('data.controls.can_record_payment', true);

        $this->assertDatabaseMissing('installment_schedules', [
            'sale_agreement_id' => $id,
        ]);
    }

    public function test_flexible_payments_update_running_balance_and_progress(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->activePaymentPlanContract($company, $user, 100000, 20000, 3);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 30000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])
            ->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('data.financials.paid_amount', 30000)
            ->assertJsonPath('data.financials.balance_due', 70000)
            ->assertJsonPath('data.financials.progress_percent', 30);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 70000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('data.status.value', Agreement::STATUS_COMPLETED)
            ->assertJsonPath('data.financials.balance_due', 0)
            ->assertJsonPath('data.financials.progress_percent', 100);
    }

    public function test_legacy_installment_payment_endpoint_delegates_to_record_payment(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->activePaymentPlanContract($company, $user, 80000, 10000, 2);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-installment-payment", [
            'amount' => 80000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('data.financials.paid_amount', 80000);
    }

    public function test_payment_plan_requires_duration_or_end_date(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
        ]);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);
        $reservation = app(ReservationService::class)->create($user, [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 5000,
            'record_deposit' => true,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 120000,
            'down_payment' => 20000,
            'is_payment_plan' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plan_duration_years']);
    }

    public function test_download_payment_plan_statement_pdf(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF not installed.');
        }

        [$company, $user] = $this->actingCompanyUser();
        $id = $this->activePaymentPlanContract($company, $user);

        $response = $this->get("/api/v1/sale-agreements/{$id}/payment-plan-statement");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    public function test_generate_schedule_endpoint_returns_retired_message(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->activePaymentPlanContract($company, $user);

        $this->postJson("/api/v1/sale-agreements/{$id}/generate-schedule")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['message' => 'Fixed instalment schedules are retired. Payment plans use flexible collection against running balance.']);
    }
}
