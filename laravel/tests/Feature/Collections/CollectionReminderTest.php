<?php

namespace Tests\Feature\Collections;

use App\Enums\CollectionReminderType;
use App\Jobs\SendCollectionReminderJob;
use App\Mail\CollectionReminderMail;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\CollectionReminderLog;
use App\Models\Company;
use App\Models\DelinquencyFlag;
use App\Models\MonthlyInvoice;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Collections\CollectionReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CollectionReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_reminder_queues_for_invoice_due_in_seven_days(): void
    {
        Queue::fake();

        [$company, $invoice] = $this->seedInvoiceWithTenant(
            dueDate: '2026-06-15',
            email: 'tenant@example.com',
        );

        $stats = app(CollectionReminderService::class)->queueDueReminders(
            $company,
            Carbon::parse('2026-06-08'),
        );

        $this->assertSame(1, $stats['queued']);
        $this->assertDatabaseHas('collection_reminder_logs', [
            'monthly_invoice_id' => $invoice->id,
            'reminder_type' => CollectionReminderType::BeforeDue7->value,
            'status' => 'queued',
        ]);

        Queue::assertPushed(SendCollectionReminderJob::class);
    }

    public function test_opt_out_tenant_skips_reminder(): void
    {
        Queue::fake();

        [$company] = $this->seedInvoiceWithTenant(
            dueDate: '2026-06-15',
            email: 'tenant@example.com',
            optOut: true,
        );

        $stats = app(CollectionReminderService::class)->queueDueReminders(
            $company,
            Carbon::parse('2026-06-08'),
        );

        $this->assertSame(0, $stats['queued']);
        $this->assertDatabaseCount('collection_reminder_logs', 0);
    }

    public function test_reminder_not_duplicated(): void
    {
        Queue::fake();

        [$company] = $this->seedInvoiceWithTenant(
            dueDate: '2026-06-15',
            email: 'tenant@example.com',
        );

        $service = app(CollectionReminderService::class);
        $service->queueDueReminders($company, Carbon::parse('2026-06-08'));
        $stats = $service->queueDueReminders($company, Carbon::parse('2026-06-08'));

        $this->assertSame(0, $stats['queued']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertDatabaseCount('collection_reminder_logs', 1);
    }

    public function test_manual_bulk_remind_api_queues_jobs(): void
    {
        Queue::fake();

        [$company, $invoice, $user, $tenant] = $this->seedInvoiceWithTenant(
            dueDate: '2026-06-01',
            email: 'tenant@example.com',
            withUser: true,
        );

        $flag = DelinquencyFlag::query()->create([
            'company_id' => $company->id,
            'monthly_invoice_id' => $invoice->id,
            'first_overdue_date' => '2026-06-02',
            'escalation_stage' => 'first_notice',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/reports/delinquency/remind', [
            'flag_ids' => [$flag->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.queued', 1);

        $this->assertDatabaseHas('collection_reminder_logs', [
            'monthly_invoice_id' => $invoice->id,
            'reminder_type' => CollectionReminderType::Manual->value,
            'tenant_id' => $tenant->id,
        ]);

        Queue::assertPushed(SendCollectionReminderJob::class);
    }

    public function test_dispatch_log_sends_email(): void
    {
        Mail::fake();

        [$company, $invoice, , $tenant] = $this->seedInvoiceWithTenant(
            dueDate: '2026-06-15',
            email: 'tenant@example.com',
            withUser: true,
        );

        $log = CollectionReminderLog::query()->create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'monthly_invoice_id' => $invoice->id,
            'reminder_type' => CollectionReminderType::Manual,
            'channel' => 'email',
            'status' => 'queued',
        ]);

        app(CollectionReminderService::class)->dispatchLog($log);

        Mail::assertSent(CollectionReminderMail::class, function (CollectionReminderMail $mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });

        $log->refresh();
        $this->assertSame('sent', $log->status);
    }

    /**
     * @return array{0: Company, 1: MonthlyInvoice, 2?: User, 3?: Tenant}
     */
    protected function seedInvoiceWithTenant(
        string $dueDate,
        string $email,
        bool $optOut = false,
        bool $withUser = false,
    ): array {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id]);
        $apartment = Apartment::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
        ]);
        $tenant = Tenant::factory()->create([
            'company_id' => $company->id,
            'email' => $email,
            'reminder_opt_out' => $optOut,
        ]);

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
            'invoice_number' => 'INV-REM-'.str()->random(4),
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
            return [$company, $invoice, $user, $tenant];
        }

        return [$company, $invoice];
    }
}
