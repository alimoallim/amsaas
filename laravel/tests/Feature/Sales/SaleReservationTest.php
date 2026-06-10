<?php

namespace Tests\Feature\Sales;

use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\Company;
use App\Models\Payment;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Sales\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleReservationTest extends TestCase
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
            'market_sale_price' => 150000,
            'currency' => 'USD',
        ]);
    }

    public function test_create_reservation_with_deposit_marks_unit_reserved_and_confirms(): void
    {
        [$company] = $this->actingCompanyUser();
        $apartment = $this->saleApartment($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        $response = $this->postJson('/api/v1/sale-reservations', [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 5000,
            'expiry_date' => now()->addDays(5)->toDateString(),
            'record_deposit' => true,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ])->assertCreated();

        $reservationId = $response->json('data.id');

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_RESERVED, $apartment->inventory_status);

        $this->assertDatabaseHas('sale_reservations', [
            'id' => $reservationId,
            'status' => SaleReservation::STATUS_CONFIRMED,
            'buyer_id' => $buyer->id,
        ]);

        $this->assertSame(1, Payment::query()->where('buyer_id', $buyer->id)->count());
    }

    public function test_second_reservation_on_same_unit_is_rejected(): void
    {
        [$company] = $this->actingCompanyUser();
        $apartment = $this->saleApartment($company);
        $buyerA = Buyer::factory()->create(['company_id' => $company->id]);
        $buyerB = Buyer::factory()->create(['company_id' => $company->id]);

        $this->postJson('/api/v1/sale-reservations', [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyerA->id,
            'deposit_amount' => 1000,
            'expiry_date' => now()->addDays(3)->toDateString(),
        ])->assertCreated();

        $this->postJson('/api/v1/sale-reservations', [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyerB->id,
            'deposit_amount' => 1000,
            'expiry_date' => now()->addDays(3)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_expire_job_releases_unpaid_reservation(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $apartment = $this->saleApartment($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        app(ReservationService::class)->create($user, [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 2500,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_RESERVED, $apartment->inventory_status);

        $stats = app(ReservationService::class)->expireDueReservations(
            Carbon::today(),
            (string) $company->id,
        );

        $this->assertSame(1, $stats['expired']);

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_AVAILABLE, $apartment->inventory_status);

        $this->assertDatabaseHas('sale_reservations', [
            'apartment_id' => $apartment->id,
            'status' => SaleReservation::STATUS_EXPIRED,
        ]);
    }

    public function test_cancel_active_reservation_releases_unit(): void
    {
        [$company] = $this->actingCompanyUser();
        $apartment = $this->saleApartment($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        $create = $this->postJson('/api/v1/sale-reservations', [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 1000,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->postJson("/api/v1/sale-reservations/{$id}/cancel", [
            'reason' => 'Buyer withdrew',
        ])->assertOk();

        $apartment->refresh();
        $this->assertSame(Apartment::STATUS_AVAILABLE, $apartment->inventory_status);
    }

    public function test_record_deposit_confirms_pending_reservation(): void
    {
        [$company] = $this->actingCompanyUser();
        $apartment = $this->saleApartment($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);

        $create = $this->postJson('/api/v1/sale-reservations', [
            'apartment_id' => $apartment->id,
            'buyer_id' => $buyer->id,
            'deposit_amount' => 3000,
        ])->assertCreated();

        $id = $create->json('data.id');
        $this->assertSame('pending_deposit', $create->json('data.status'));

        $this->postJson("/api/v1/sale-reservations/{$id}/deposit", [
            'amount' => 3000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ])->assertOk()
            ->assertJsonPath('data.status', SaleReservation::STATUS_CONFIRMED);
    }
}
