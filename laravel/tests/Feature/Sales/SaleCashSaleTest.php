<?php

namespace Tests\Feature\Sales;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\Company;
use App\Models\SaleAgreement;
use App\Models\SalePaymentAllocation;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Sales\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleCashSaleTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    private function saleApartment(Company $company): Apartment
    {
        $building = Building::factory()->create(['company_id' => $company->id]);

        return Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'listing_type' => Apartment::LISTING_TYPE_SALE,
            'inventory_status' => Apartment::STATUS_AVAILABLE,
            'market_sale_price' => 200000,
            'currency' => 'USD',
        ]);
    }

    private function confirmedReservation(Company $company, User $user): SaleReservation
    {
        $apartment = $this->saleApartment($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        return app(ReservationService::class)->create($user, [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 10000,
            'record_deposit' => true,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
    }

    private function activeCashContract(Company $company, User $user, float $salePrice = 200000): array
    {
        $reservation = $this->confirmedReservation($company, $user);

        $response = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => $salePrice,
            'down_payment' => 50000,
            'is_installment_sale' => false,
            'execute' => true,
        ])->assertCreated();

        $id = $response->json('data.id');
        $apartment = $reservation->apartment->fresh();

        return [$id, $apartment, $reservation];
    }

    public function test_partial_payment_reduces_balance(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        [$id] = $this->activeCashContract($company, $user, 150000);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 60000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])
            ->assertOk()
            ->assertJsonPath('data.financials.paid_amount', 60000)
            ->assertJsonPath('data.financials.balance_due', 90000)
            ->assertJsonPath('completed', false)
            ->assertJsonPath('data.status.value', Agreement::STATUS_ACTIVE);

        $this->assertDatabaseHas('sale_payment_allocations', [
            'sale_agreement_id' => $id,
            'amount_allocated' => 60000,
        ]);
    }

    public function test_full_payment_completes_cash_sale(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        [$id, $apartment] = $this->activeCashContract($company, $user, 120000);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 120000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('data.status.value', Agreement::STATUS_COMPLETED)
            ->assertJsonPath('data.financials.balance_due', 0)
            ->assertJsonPath('data.ownership.ownership_transferred', false)
            ->assertJsonPath('data.controls.can_approve_legal', true);

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_SOLD, $apartment->inventory_status);

        $sale = SaleAgreement::query()->find($id);
        $this->assertFalse($sale->ownership_transferred);
        $this->assertNotNull($sale->closing_date);
    }

    public function test_payment_plan_contract_accepts_flexible_payment(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $create = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 200000,
            'down_payment' => 40000,
            'is_payment_plan' => true,
            'plan_duration_years' => 5,
            'contract_date' => '2026-01-01',
            'execute' => true,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])
            ->assertOk()
            ->assertJsonPath('data.financials.paid_amount', 50000)
            ->assertJsonPath('data.financials.balance_due', 150000);
    }

    public function test_overpayment_is_capped_to_balance_due(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        [$id] = $this->activeCashContract($company, $user, 80000);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('data.financials.paid_amount', 80000);

        $this->assertSame(
            1,
            SalePaymentAllocation::query()->where('sale_agreement_id', $id)->count(),
        );
    }

    public function test_completed_sale_cannot_record_more_payments(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        [$id] = $this->activeCashContract($company, $user, 50000);

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertOk();

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SALE_NOT_ACTIVE');
    }
}
