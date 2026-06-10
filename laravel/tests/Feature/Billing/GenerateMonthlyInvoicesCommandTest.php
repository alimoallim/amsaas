<?php

namespace Tests\Feature\Billing;

use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Company;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GenerateMonthlyInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_requires_company_id(): void
    {
        $exitCode = Artisan::call('billing:generate-monthly');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('company_id', Artisan::output());
    }

    public function test_command_fails_for_unknown_company(): void
    {
        $exitCode = Artisan::call('billing:generate-monthly', [
            '--company_id' => '019ea199-0000-7000-8000-000000000099',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Company not found', Artisan::output());
    }

    public function test_command_runs_monthly_close_for_active_agreements(): void
    {
        $company = Company::factory()->create();
        User::factory()->create(['company_id' => $company->id]);

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
        ]);

        RentalAgreement::query()->create([
            'id' => $agreement->id,
            'monthly_rent' => 800,
            'security_deposit' => 0,
            'payment_due_day' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $exitCode = Artisan::call('billing:generate-monthly', [
            '--company_id' => $company->id,
            '--year' => now()->year,
            '--month' => now()->month,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Monthly billing close completed', Artisan::output());
    }
}
