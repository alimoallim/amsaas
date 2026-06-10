<?php

namespace Tests\Feature\Sales;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\ApartmentOwnershipHistory;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\Company;
use App\Models\SaleAgreement;
use App\Models\SaleOwnershipApproval;
use App\Models\SaleReservation;
use App\Models\User;
use App\Services\Sales\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleOwnershipTransferTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    private function completedCashContract(Company $company, User $user, float $salePrice = 100000): string
    {
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
            'deposit_amount' => 5000,
            'record_deposit' => true,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $create = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => $salePrice,
            'down_payment' => 20000,
            'is_installment_sale' => false,
            'execute' => true,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->postJson("/api/v1/sale-agreements/{$id}/record-payment", [
            'amount' => $salePrice,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertOk()->assertJsonPath('completed', true);

        return $id;
    }

    public function test_completed_sale_awaits_ownership_approvals(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->completedCashContract($company, $user);

        $this->getJson("/api/v1/sale-agreements/{$id}")
            ->assertOk()
            ->assertJsonPath('data.ownership.ownership_transferred', false)
            ->assertJsonPath('data.ownership.pending_steps', SaleOwnershipApproval::STEPS)
            ->assertJsonPath('data.controls.can_approve_legal', true);

        $sale = SaleAgreement::query()->find($id);
        $this->assertFalse($sale->ownership_transferred);
        $this->assertNotNull($sale->closing_date);
    }

    public function test_three_approvals_finalize_ownership_transfer(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->completedCashContract($company, $user);

        foreach (SaleOwnershipApproval::STEPS as $index => $step) {
            $response = $this->postJson("/api/v1/sale-agreements/{$id}/approve-ownership", [
                'step' => $step,
                'notes' => "Approved by test {$step}",
            ])->assertOk();

            $finalized = $index === count(SaleOwnershipApproval::STEPS) - 1;
            $response->assertJsonPath('finalized', $finalized);
        }

        $this->getJson("/api/v1/sale-agreements/{$id}")
            ->assertOk()
            ->assertJsonPath('data.ownership.ownership_transferred', true)
            ->assertJsonPath('data.ownership.pending_steps', [])
            ->assertJsonPath('data.controls.can_issue_title_deed', true);

        $sale = SaleAgreement::query()->find($id);
        $this->assertTrue($sale->ownership_transferred);
        $this->assertNotNull($sale->ownership_transfer_date);

        $this->assertDatabaseHas('apartment_ownership_history', [
            'sale_agreement_id' => $id,
            'recorded_by' => $user->id,
        ]);

        $this->assertSame(
            1,
            ApartmentOwnershipHistory::query()->where('sale_agreement_id', $id)->count(),
        );
    }

    public function test_duplicate_approval_step_is_rejected(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->completedCashContract($company, $user);

        $this->postJson("/api/v1/sale-agreements/{$id}/approve-ownership", [
            'step' => SaleOwnershipApproval::STEP_LEGAL,
        ])->assertOk();

        $this->postJson("/api/v1/sale-agreements/{$id}/approve-ownership", [
            'step' => SaleOwnershipApproval::STEP_LEGAL,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'OWNERSHIP_STEP_ALREADY_APPROVED');
    }

    public function test_title_deed_requires_finalized_transfer(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->completedCashContract($company, $user);

        $this->postJson("/api/v1/sale-agreements/{$id}/issue-title-deed", [
            'title_deed_number' => 'TD-2026-001',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'OWNERSHIP_NOT_TRANSFERRED');
    }

    public function test_title_deed_issued_after_transfer(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->completedCashContract($company, $user);

        foreach (SaleOwnershipApproval::STEPS as $step) {
            $this->postJson("/api/v1/sale-agreements/{$id}/approve-ownership", [
                'step' => $step,
            ])->assertOk();
        }

        $this->postJson("/api/v1/sale-agreements/{$id}/issue-title-deed", [
            'title_deed_number' => 'TD-2026-042',
            'notes' => 'Registered at land office',
        ])
            ->assertOk()
            ->assertJsonPath('data.ownership.title_deed_issued', true)
            ->assertJsonPath('data.ownership.title_deed_number', 'TD-2026-042');

        $this->assertDatabaseHas('apartment_ownership_history', [
            'sale_agreement_id' => $id,
            'title_deed_number' => 'TD-2026-042',
        ]);
    }

    public function test_apartment_ownership_history_endpoint(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        $id = $this->completedCashContract($company, $user);
        $apartmentId = Agreement::query()->find($id)->apartment_id;

        foreach (SaleOwnershipApproval::STEPS as $step) {
            $this->postJson("/api/v1/sale-agreements/{$id}/approve-ownership", [
                'step' => $step,
            ])->assertOk();
        }

        $this->getJson("/api/v1/apartments/{$apartmentId}/ownership-history")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.sale_agreement.id', $id);
    }
}
