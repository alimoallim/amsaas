<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_defaults_creates_eleven_posting_accounts(): void
    {
        $company = Company::factory()->create();

        $this->assertDatabaseCount('accounts', 11);

        $codes = Account::query()
            ->where('company_id', $company->id)
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $this->assertEquals(
            ['1110', '1115', '1116', '1117', '1120', '2120', '2130', '4100', '4110', '4140', '4150'],
            $codes,
        );

        $this->assertTrue(
            Account::query()
                ->where('company_id', $company->id)
                ->where('is_system', true)
                ->count() === 11,
        );
    }

    public function test_seed_defaults_is_idempotent(): void
    {
        $company = Company::factory()->create();
        $service = app(ChartOfAccountsService::class);

        $second = $service->seedDefaults($company);

        $this->assertSame(0, $second['created']);
        $this->assertSame(0, $second['updated']);
        $this->assertSame(11, $second['skipped']);
        $this->assertDatabaseCount('accounts', 11);
    }

    public function test_seed_defaults_adds_missing_accounts_for_legacy_company(): void
    {
        $company = Company::factory()->create();

        Account::query()
            ->where('company_id', $company->id)
            ->whereIn('code', ['1115', '1116', '1117', '4150'])
            ->forceDelete();

        $service = app(ChartOfAccountsService::class);
        $result = $service->seedDefaults($company);

        $this->assertSame(4, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(7, $result['skipped']);
        $this->assertDatabaseCount('accounts', 11);
    }

    public function test_seed_defaults_syncs_renamed_system_accounts(): void
    {
        $company = Company::factory()->create();

        Account::query()
            ->where('company_id', $company->id)
            ->where('code', Account::CODE_ACCOUNTS_RECEIVABLE)
            ->update(['name' => 'Trade Receivables']);

        Account::query()
            ->where('company_id', $company->id)
            ->where('code', Account::CODE_CUSTOMER_DEPOSITS_PAYABLE)
            ->update(['name' => 'Tenant Deposits Payable']);

        $result = app(ChartOfAccountsService::class)->seedDefaults($company);

        $this->assertSame(0, $result['created']);
        $this->assertSame(2, $result['updated']);
        $this->assertSame(9, $result['skipped']);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $company->id,
            'code' => Account::CODE_ACCOUNTS_RECEIVABLE,
            'name' => 'Accounts Receivable',
        ]);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $company->id,
            'code' => Account::CODE_CUSTOMER_DEPOSITS_PAYABLE,
            'name' => 'Customer Deposits Payable',
        ]);
    }

    public function test_seed_chart_command_runs_for_single_company(): void
    {
        $company = Company::factory()->create();

        Account::query()
            ->where('company_id', $company->id)
            ->where('code', Account::CODE_DEFERRED_REVENUE)
            ->update(['name' => 'Unearned Rent']);

        $this->artisan('accounting:seed-chart', ['--company_id' => $company->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('accounts', [
            'company_id' => $company->id,
            'code' => Account::CODE_DEFERRED_REVENUE,
            'name' => 'Deferred Revenue',
        ]);
    }
}
