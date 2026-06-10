<?php

namespace Tests\Feature\Collections;

use App\Enums\DelinquencyEscalationStage;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\DelinquencyFlag;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Collections\DelinquencyTrackingService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DelinquencyTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_flags_overdue_invoice_and_creates_delinquency_record(): void
    {
        [$company, $invoice] = $this->seedOverdueInvoice('2026-06-01');

        $this->artisan('collections:flag-overdue', [
            '--company_id' => $company->id,
            '--as-of' => '2026-06-15',
        ])->assertSuccessful();

        $invoice->refresh();
        $this->assertSame('overdue', $invoice->status);

        $this->assertDatabaseHas('delinquency_flags', [
            'monthly_invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'escalation_stage' => DelinquencyEscalationStage::FirstNotice->value,
        ]);
    }

    public function test_process_company_escalates_existing_flag(): void
    {
        [$company, $invoice] = $this->seedOverdueInvoice('2026-05-01');

        $tracking = app(DelinquencyTrackingService::class);
        $tracking->processCompany($company, Carbon::parse('2026-06-01'));

        $flag = DelinquencyFlag::query()->where('monthly_invoice_id', $invoice->id)->first();
        $this->assertNotNull($flag);
        $this->assertSame(DelinquencyEscalationStage::SecondNotice, $flag->escalation_stage);

        $tracking->processCompany($company, Carbon::parse('2026-07-01'));

        $flag->refresh();
        $this->assertSame(DelinquencyEscalationStage::LegalHandoff, $flag->escalation_stage);
    }

    public function test_escalation_stage_boundaries(): void
    {
        $service = app(DelinquencyTrackingService::class);

        $this->assertSame(
            DelinquencyEscalationStage::FirstNotice,
            $service->escalationStageFor('2026-06-01', Carbon::parse('2026-06-14'))
        );
        $this->assertSame(
            DelinquencyEscalationStage::SecondNotice,
            $service->escalationStageFor('2026-06-01', Carbon::parse('2026-06-20'))
        );
        $this->assertSame(
            DelinquencyEscalationStage::LegalHandoff,
            $service->escalationStageFor('2026-06-01', Carbon::parse('2026-07-20'))
        );
    }

    public function test_delinquency_flag_resolved_when_invoice_paid(): void
    {
        [$company, $invoice] = $this->seedOverdueInvoice('2026-06-01');

        app(DelinquencyTrackingService::class)->processCompany(
            $company,
            Carbon::parse('2026-06-15')
        );

        app(InvoiceService::class)->applyPayment($invoice, 500);

        $flag = DelinquencyFlag::query()->where('monthly_invoice_id', $invoice->id)->first();
        $this->assertNotNull($flag->resolved_at);
    }

    public function test_delinquency_api_lists_active_flags(): void
    {
        [$company, $invoice, $user] = $this->seedOverdueInvoice('2026-06-01', withUser: true);

        app(DelinquencyTrackingService::class)->processCompany(
            $company,
            Carbon::parse('2026-06-15')
        );

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/reports/delinquency?as_of=2026-06-15')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rows.0.invoice_number', $invoice->invoice_number)
            ->assertJsonPath('data.rows.0.escalation_stage', 'first_notice');
    }

    public function test_delinquency_api_filters_by_escalation_stage(): void
    {
        [$company, $invoice, $user] = $this->seedOverdueInvoice('2026-02-01', withUser: true);

        app(DelinquencyTrackingService::class)->processCompany(
            $company,
            Carbon::parse('2026-06-15')
        );

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/reports/delinquency?escalation_stage=legal_handoff&as_of=2026-06-15')
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $this->getJson('/api/v1/reports/delinquency?escalation_stage=first_notice&as_of=2026-06-15')
            ->assertOk()
            ->assertJsonPath('data.total', 0);
    }

    /**
     * @return array{0: Company, 1: MonthlyInvoice, 2?: User}
     */
    protected function seedOverdueInvoice(string $dueDate, bool $withUser = false): array
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
            'monthly_rent' => 500,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-DELQ-'.str()->random(4),
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-01',
            'due_date' => $dueDate,
            'subtotal_rent' => 500,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        if ($withUser) {
            return [$company, $invoice, $user];
        }

        return [$company, $invoice];
    }
}
