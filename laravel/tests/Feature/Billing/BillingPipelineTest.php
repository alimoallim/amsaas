<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\InvoiceLineItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\ChargeWorkflowService;
use App\Services\Billing\GenerateChargeService;
use App\Services\Billing\InvoiceConsolidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_reading_generates_charge_then_consolidates_to_monthly_invoice(): void
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
            'start_date' => now()->subMonth()->toDateString(),
            'currency' => 'USD',
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 1000,
            'security_deposit' => 500,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);

        $electricityModel = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'meter_type' => Meter::UTILITY_ELECTRICITY,
            'unit_rate' => '2.0000',
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
            'auto_generate' => true,
        ]);

        AgreementCharge::query()->create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'charge_model_id' => $electricityModel->id,
            'charge_type_id' => $chargeType->id,
            'billing_start_date' => $agreement->start_date,
            'status' => AgreementCharge::STATUS_ACTIVE,
        ]);

        $meter = Meter::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'utility_type' => Meter::UTILITY_ELECTRICITY,
            'status' => Meter::STATUS_ACTIVE,
            'current_reading' => 100,
            'initial_reading' => 100,
        ]);

        $reading = MeterReading::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'meter_id' => $meter->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'reading_date' => now()->toDateString(),
            'previous_reading' => 100,
            'current_reading' => 150,
            'consumption' => 50,
            'reading_type' => MeterReading::TYPE_ACTUAL,
            'reading_source' => MeterReading::SOURCE_MANUAL,
            'status' => MeterReading::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $charges = app(GenerateChargeService::class)->generateFromMeterReading($reading);

        $this->assertCount(1, $charges);
        $charge = $charges->first();
        $this->assertEquals(Charge::STATUS_PENDING, $charge->status);

        app(ChargeWorkflowService::class)->approve($charge, $user);

        $charge = $charge->fresh();
        $this->assertContains($charge->status, [Charge::STATUS_APPROVED, Charge::STATUS_INVOICED]);

        if ($charge->status === Charge::STATUS_APPROVED) {
            $billingDate = Carbon::now()->startOfMonth();
            $result = (new InvoiceConsolidationService($user))->consolidate(
                RentalAgreement::findOrFail($agreement->id),
                $billingDate,
            );

            $invoice = $result->invoice;
            $this->assertInstanceOf(MonthlyInvoice::class, $invoice);
            $this->assertTrue($result->wasCreated() || $result->wasAppended());
            $this->assertEqualsWithDelta(100, (float) $invoice->subtotal_utilities, 0.0001);

            $charge = Charge::findOrFail($charges->first()->id);
        }

        $this->assertNotNull($charge->invoice_id);
        $this->assertEquals(Charge::STATUS_INVOICED, $charge->status);

        $this->assertGreaterThanOrEqual(
            1,
            InvoiceLineItem::where('monthly_invoice_id', $charge->invoice_id)->count()
        );
    }
}
