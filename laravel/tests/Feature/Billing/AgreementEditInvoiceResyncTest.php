<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\BillingItem;
use App\Models\Building;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\BillingProcessorService;
use App\Services\Billing\InvoiceConsolidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgreementEditInvoiceResyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_charge_model_to_agreement_appends_to_existing_draft_invoice(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        $rentModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_AGREEMENT_RENT,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $parkingModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_FLAT_FEE,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $agreement = Agreement::factory()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'status' => Agreement::STATUS_ACTIVE,
            'start_date' => now()->startOfMonth()->toDateString(),
        ]);

        $rental = RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 1000,
            'security_deposit' => 0,
            'payment_due_day' => 5,
            'billing_cycle' => 'monthly',
        ]);

        AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $rentModel->id,
            'charge_type_id' => $chargeType->id,
            'billing_start_date' => $agreement->start_date,
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $billingDate = Carbon::now()->startOfMonth();

        app(BillingProcessorService::class, [
            'user' => $user,
            'billingDate' => $billingDate,
        ])->ensureBillingItemsForAgreement($agreement->id);

        $consolidation = (new InvoiceConsolidationService($user))->consolidate($rental, $billingDate);
        $invoice = $consolidation->invoice;

        $this->assertInstanceOf(MonthlyInvoice::class, $invoice);
        $this->assertEquals(1, InvoiceLineItem::where('monthly_invoice_id', $invoice->id)->count());
        $this->assertEqualsWithDelta(1000, (float) $invoice->subtotal_rent, 0.01);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/rental-agreements/{$agreement->id}", [
            'monthly_rent' => 1000,
            'payment_due_day' => 5,
            'currency' => 'USD',
            'auto_renew' => false,
            'renewal_notice_days' => 30,
            'recurring_charges' => [
                [
                    'charge_model_id' => $parkingModel->id,
                    'override_amount' => 50,
                    'custom_name' => 'Parking',
                ],
            ],
        ])->assertOk();

        $invoice->refresh();

        $this->assertEquals(2, InvoiceLineItem::where('monthly_invoice_id', $invoice->id)->count());
        $this->assertEqualsWithDelta(1000, (float) $invoice->subtotal_rent, 0.01);
        $this->assertEqualsWithDelta(50, (float) $invoice->subtotal_services, 0.01);

        $this->assertEquals(
            2,
            BillingItem::query()
                ->where('agreement_id', $agreement->id)
                ->where('posted_to_invoice', true)
                ->count()
        );
    }
}
