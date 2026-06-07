<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\ChargeType;
use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\ConsolidationResult;
use App\Services\Billing\InvoiceConsolidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceConsolidationAppendTest extends TestCase
{
    use RefreshDatabase;

    public function test_utility_charges_append_to_existing_monthly_invoice(): void
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
            'monthly_rent' => 550,
            'security_deposit' => 0,
            'payment_due_day' => 5,
            'billing_cycle' => 'monthly',
        ]);

        $billingDate = Carbon::now()->startOfMonth();

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-EXISTING-001',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => $billingDate->year,
            'billing_month' => $billingDate->month,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal_rent' => 550,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 100,
            'status' => 'partially_paid',
        ]);

        $chargeType = ChargeType::factory()->create(['company_id' => $company->id]);
        $model = ChargeModel::factory()->create([
            'company_id' => $company->id,
            'charge_type_id' => $chargeType->id,
            'pricing_strategy' => ChargeModel::STRATEGY_METERED,
        ]);

        $utilityCharge = Charge::query()->create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'charge_number' => 'CHG-TEST-001',
            'company_id' => $company->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'rental_agreement_id' => $agreement->id,
            'charge_type_id' => $chargeType->id,
            'charge_model_id' => $model->id,
            'category' => Charge::CATEGORY_UTILITY,
            'billing_strategy' => ChargeModel::STRATEGY_METERED,
            'status' => Charge::STATUS_APPROVED,
            'currency' => 'USD',
            'quantity' => 40,
            'unit_rate' => 2.5,
            'subtotal_amount' => 100,
            'total_amount' => 100,
            'service_period_start' => $billingDate->toDateString(),
            'service_period_end' => $billingDate->copy()->endOfMonth()->toDateString(),
            'description' => 'Electricity consumption',
            'charged_at' => now(),
        ]);

        $result = (new InvoiceConsolidationService($user))->consolidate(
            RentalAgreement::findOrFail($agreement->id),
            $billingDate,
        );

        $this->assertTrue($result->wasAppended());
        $this->assertSame(ConsolidationResult::OUTCOME_APPENDED, $result->outcome);

        $invoice->refresh();
        $this->assertEqualsWithDelta(100, (float) $invoice->subtotal_utilities, 0.01);
        $this->assertEquals($invoice->id, $utilityCharge->fresh()->invoice_id);
        $this->assertSame(Charge::STATUS_INVOICED, $utilityCharge->fresh()->status);
        $this->assertSame('partially_paid', $invoice->status);
    }
}
