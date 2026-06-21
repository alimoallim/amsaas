<?php

namespace Tests\Feature\Policies;

use App\Models\Account;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Buyer;
use App\Models\Building;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\SaleAgreement;
use App\Models\SaleReservation;
use App\Models\Tenant;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\RentalAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_invoice_policy_scopes_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $invoiceB = MonthlyInvoice::factory()->create(['company_id' => $companyB->id]);

        $this->assertTrue($userA->can('viewAny', MonthlyInvoice::class));
        $this->assertFalse($userA->can('view', $invoiceB));
        $this->assertFalse($userA->can('void', $invoiceB));
    }

    public function test_payment_policy_scopes_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $tenantB = Tenant::factory()->create(['company_id' => $companyB->id]);

        $paymentB = Payment::query()->create([
            'company_id' => $companyB->id,
            'tenant_id' => $tenantB->id,
            'amount' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'receipt_number' => 'RCPT-B-001',
            'status' => 'completed',
        ]);

        $this->assertTrue($userA->can('viewAny', Payment::class));
        $this->assertFalse($userA->can('view', $paymentB));
        $this->assertFalse($userA->can('refund', $paymentB));
    }

    public function test_rental_agreement_policy_scopes_via_agreement_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $buildingB = Building::factory()->create(['company_id' => $companyB->id]);
        $apartmentB = Apartment::factory()->create([
            'company_id' => $companyB->id,
            'building_id' => $buildingB->id,
        ]);
        $tenantB = Tenant::factory()->create(['company_id' => $companyB->id]);

        $agreement = Agreement::factory()
            ->withRentalAgreement()
            ->create([
                'company_id' => $companyB->id,
                'apartment_id' => $apartmentB->id,
                'tenant_id' => $tenantB->id,
            ]);

        $rental = RentalAgreement::query()->findOrFail($agreement->id);

        $this->assertTrue($userA->can('viewAny', RentalAgreement::class));
        $this->assertFalse($userA->can('view', $rental));
        $this->assertFalse($userA->can('update', $rental));
    }

    public function test_property_policies_scope_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $tenantB = Tenant::factory()->create(['company_id' => $companyB->id]);
        $buildingB = Building::factory()->create(['company_id' => $companyB->id]);
        $apartmentB = Apartment::factory()->create([
            'company_id' => $companyB->id,
            'building_id' => $buildingB->id,
        ]);

        $this->assertFalse($userA->can('view', $tenantB));
        $this->assertFalse($userA->can('view', $buildingB));
        $this->assertFalse($userA->can('delete', $apartmentB));
        $this->assertTrue($userA->can('viewAny', Apartment::class));
    }

    public function test_sales_policies_scope_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $buyerB = Buyer::factory()->create(['company_id' => $companyB->id]);
        $buildingB = Building::factory()->create(['company_id' => $companyB->id]);
        $apartmentB = Apartment::factory()->create([
            'company_id' => $companyB->id,
            'building_id' => $buildingB->id,
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $companyB->id,
            'apartment_id' => $apartmentB->id,
            'buyer_id' => $buyerB->id,
            'tenant_id' => null,
            'agreement_type' => Agreement::TYPE_SALE,
        ]);

        $sale = SaleAgreement::query()->create([
            'id' => $agreement->id,
            'sale_price' => 100000,
            'down_payment' => 20000,
            'financed_amount' => 80000,
            'is_installment_sale' => false,
        ]);
        $reservation = SaleReservation::factory()->create([
            'company_id' => $companyB->id,
            'apartment_id' => $apartmentB->id,
            'buyer_id' => $buyerB->id,
        ]);

        $this->assertFalse($userA->can('view', $buyerB));
        $this->assertFalse($userA->can('view', $sale));
        $this->assertFalse($userA->can('recordDeposit', $reservation));
        $this->assertTrue($userA->can('viewAny', SaleReservation::class));
    }

    public function test_h8_cleanup_policies_scope_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $accountB = Account::factory()->create(['company_id' => $companyB->id]);
        $chargeTypeB = ChargeType::factory()->create(['company_id' => $companyB->id]);
        $meterB = Meter::factory()->create(['company_id' => $companyB->id]);
        $readingB = MeterReading::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $companyB->id,
            'meter_id' => $meterB->id,
            'building_id' => $meterB->building_id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 0,
            'current_reading' => 100,
            'consumption' => 100,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_DRAFT,
        ]);

        $this->assertFalse($userA->can('update', $accountB));
        $this->assertFalse($userA->can('update', $chargeTypeB));
        $this->assertFalse($userA->can('approve', $readingB));
        $this->assertTrue($userA->can('bulkManage', MeterReading::class));
        $this->assertTrue($userA->can('create', Company::class));
    }
}
