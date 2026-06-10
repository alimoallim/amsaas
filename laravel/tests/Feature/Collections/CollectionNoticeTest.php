<?php

namespace Tests\Feature\Collections;

use App\Enums\DelinquencyEscalationStage;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\CollectionNotice;
use App\Models\Company;
use App\Models\DelinquencyFlag;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CollectionNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_notice_creates_pdf_record(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF not installed.');
        }

        [$user, $flag, $invoice] = $this->seedDelinquentFlag();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/reports/delinquency/notices', [
            'flag_id' => $flag->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notice_type', DelinquencyEscalationStage::FirstNotice->value);

        $notice = CollectionNotice::query()->where('delinquency_flag_id', $flag->id)->first();
        $this->assertNotNull($notice);
        $this->assertTrue(Storage::disk('local')->exists($notice->file_path));
    }

    public function test_download_notice_returns_pdf(): void
    {
        [$user, $flag] = $this->seedDelinquentFlag();

        $path = "collection-notices/{$flag->company_id}/test-notice.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $notice = CollectionNotice::query()->create([
            'company_id' => $flag->company_id,
            'delinquency_flag_id' => $flag->id,
            'monthly_invoice_id' => $flag->monthly_invoice_id,
            'notice_type' => DelinquencyEscalationStage::FirstNotice,
            'file_path' => $path,
            'generated_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->get("/api/v1/reports/notices/{$notice->id}/download")
            ->assertOk();
    }

    public function test_notice_copy_matches_escalation_stage(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF not installed.');
        }

        [$user, $flag] = $this->seedDelinquentFlag(
            escalation: DelinquencyEscalationStage::LegalHandoff,
        );

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/reports/delinquency/notices', [
            'flag_id' => $flag->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.notice_type', 'legal_handoff')
            ->assertJsonPath('data.notice_label', 'Legal handoff');
    }

    /**
     * @return array{0: User, 1: DelinquencyFlag, 2: MonthlyInvoice}
     */
    protected function seedDelinquentFlag(
        DelinquencyEscalationStage $escalation = DelinquencyEscalationStage::FirstNotice,
    ): array {
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
            'monthly_rent' => 600,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $invoice = MonthlyInvoice::query()->create([
            'company_id' => $company->id,
            'apartment_id' => $apartment->id,
            'invoice_number' => 'INV-NOTICE-001',
            'contract_type' => 'rental',
            'contract_id' => $agreement->id,
            'billing_year' => 2026,
            'billing_month' => 6,
            'issue_date' => '2026-06-01',
            'due_date' => '2026-05-15',
            'subtotal_rent' => 600,
            'subtotal_utilities' => 0,
            'subtotal_services' => 0,
            'subtotal_installment' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'status' => 'overdue',
        ]);

        $flag = DelinquencyFlag::query()->create([
            'company_id' => $company->id,
            'monthly_invoice_id' => $invoice->id,
            'first_overdue_date' => '2026-05-16',
            'escalation_stage' => $escalation,
        ]);

        return [$user, $flag, $invoice];
    }
}
