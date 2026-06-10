<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\SaleDepositApplication;
use App\Models\User;
use App\Services\Sales\ReservationService;
use App\Services\Sales\SaleAgreementPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleDepositPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_deposit_application_posts_liability_to_ar(): void
    {
        [$company, $user, $saleId] = $this->activeSaleFromReservation(depositAmount: 10000, salePrice: 150000);
        Sanctum::actingAs($user);

        $result = app(SaleAgreementPostingService::class)->applyReservationDeposit($user, $saleId, [
            'amount' => 10000,
        ]);

        $application = $result['application'];

        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_SALE_DEPOSIT_APPLICATION)
            ->where('source_id', $application->id)
            ->with('lines.account')
            ->firstOrFail();

        $deposit = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_CUSTOMER_DEPOSITS_PAYABLE);
        $ar = $entry->lines->first(fn ($line) => $line->account?->code === Account::CODE_ACCOUNTS_RECEIVABLE);

        $this->assertNotNull($deposit);
        $this->assertNotNull($ar);
        $this->assertSame('10000.0000', (string) $deposit->debit_amount);
        $this->assertSame('10000.0000', (string) $ar->credit_amount);

        $sale = $result['sale'];
        $this->assertSame(10000.0, $sale->paidAmount());
        $this->assertSame(140000.0, $sale->balanceDue());
        $this->assertFalse($result['completed']);
    }

    public function test_sale_deposit_application_can_complete_cash_sale(): void
    {
        [$company, $user, $saleId] = $this->activeSaleFromReservation(depositAmount: 50000, salePrice: 50000);
        Sanctum::actingAs($user);

        $result = app(SaleAgreementPostingService::class)->applyReservationDeposit($user, $saleId);

        $this->assertTrue($result['completed']);
        $this->assertSame(Agreement::STATUS_COMPLETED, $result['sale']->agreement->status);
        $this->assertSame(0.0, $result['sale']->balanceDue());
        $this->assertSame(1, SaleDepositApplication::query()->where('sale_agreement_id', $saleId)->count());
    }

    public function test_sale_agreement_show_includes_deposit_ledger(): void
    {
        [$company, $user, $saleId] = $this->activeSaleFromReservation(depositAmount: 7500, salePrice: 120000);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sale-agreements/{$saleId}");

        $response->assertOk()
            ->assertJsonPath('data.financials.deposit_ledger.reservation_deposit', 7500)
            ->assertJsonPath('data.financials.deposit_ledger.available', 7500)
            ->assertJsonPath('data.financials.deposit_ledger.has_reservation', true);
    }

    public function test_apply_deposit_endpoint_reduces_balance_due(): void
    {
        [$company, $user, $saleId] = $this->activeSaleFromReservation(depositAmount: 8000, salePrice: 100000);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/sale-agreements/{$saleId}/apply-deposit", [
            'amount' => 8000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.financials.paid_amount', 8000)
            ->assertJsonPath('data.financials.balance_due', 92000)
            ->assertJsonPath('data.financials.deposit_applied', 8000)
            ->assertJsonPath('completed', false);
    }

    /**
     * @return array{0: Company, 1: User, 2: string}
     */
    private function activeSaleFromReservation(float $depositAmount, float $salePrice): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

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
            'deposit_amount' => $depositAmount,
            'record_deposit' => true,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-07-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/sale-agreements', [
            'sale_reservation_id' => $reservation->id,
            'sale_price' => $salePrice,
            'down_payment' => 0,
            'is_installment_sale' => false,
            'execute' => true,
        ])->assertCreated();

        return [$company, $user, $response->json('data.id')];
    }
}
