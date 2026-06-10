<?php

namespace Tests\Feature\Sales;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\Company;
use App\Models\SaleAgreement;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Sales\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleAgreementTest extends TestCase
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

    public function test_create_draft_contract_from_confirmed_reservation(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $response = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 195000,
            'down_payment' => 50000,
            'is_installment_sale' => false,
        ])->assertCreated();

        $agreementId = $response->json('data.id');

        $this->assertDatabaseHas('agreements', [
            'id' => $agreementId,
            'agreement_type' => Agreement::TYPE_SALE,
            'status' => Agreement::STATUS_DRAFT,
            'buyer_id' => $reservation->buyer_id,
        ]);

        $this->assertDatabaseHas('sale_agreements', [
            'id' => $agreementId,
            'sale_price' => 195000,
            'down_payment' => 50000,
        ]);

        $reservation->refresh();
        $this->assertSame($agreementId, $reservation->converted_agreement_id);
    }

    public function test_execute_contract_marks_unit_under_contract_and_converts_reservation(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);
        $apartment = $reservation->apartment;

        $create = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 200000,
            'down_payment' => 40000,
            'is_installment_sale' => true,
            'installment_months' => 24,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->postJson("/api/v1/sale-agreements/{$id}/execute")
            ->assertOk()
            ->assertJsonPath('data.status.value', Agreement::STATUS_ACTIVE)
            ->assertJsonPath('data.financials.price_locked', true);

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_UNDER_CONTRACT, $apartment->inventory_status);

        $reservation->refresh();
        $this->assertSame(SaleReservation::STATUS_CONVERTED, $reservation->status);
    }

    public function test_sale_price_cannot_change_after_execution(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $create = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 180000,
            'execute' => true,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->putJson("/api/v1/sale-agreements/{$id}", [
            'sale_price' => 170000,
        ])->assertStatus(422)
            ->assertJsonPath('code', 'SALE_AGREEMENT_LOCKED');
    }

    public function test_duplicate_sale_contract_for_same_unit_is_rejected(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 200000,
        ])->assertCreated();

        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        $this->postJson('/api/v1/sale-agreements', [
            'apartment_id' => $reservation->apartment_id,
            'buyer_id' => $buyer->id,
            'sale_price' => 210000,
        ])->assertStatus(422)
            ->assertJsonPath('code', 'SALE_AGREEMENT_CONFLICT');
    }

    public function test_payment_plan_contract_sets_financed_amount_and_term(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $response = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 120000,
            'down_payment' => 20000,
            'is_payment_plan' => true,
            'plan_duration_years' => 4,
            'contract_date' => '2026-03-01',
        ])->assertCreated();

        $this->assertSame(
            100000.0,
            (float) $response->json('data.financials.financed_amount'),
        );
        $this->assertSame('payment_plan', $response->json('data.payment_plan.mode'));
        $this->assertSame('2030-03-01', $response->json('data.dates.end_date'));
    }

    public function test_cancel_draft_contract(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $create = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 150000,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->postJson("/api/v1/sale-agreements/{$id}/cancel", [
            'reason' => 'Buyer changed mind',
        ])->assertOk()
            ->assertJsonPath('data.status.value', Agreement::STATUS_CANCELLED);
    }

    public function test_download_sales_contract_pdf(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF not installed.');
        }

        [$company, $user] = $this->actingCompanyUser();
        $reservation = $this->confirmedReservation($company, $user);

        $create = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => 180000,
            'down_payment' => 40000,
            'execute' => true,
        ])->assertCreated();

        $id = $create->json('data.id');

        $response = $this->get("/api/v1/sale-agreements/{$id}/sales-contract");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }
}
