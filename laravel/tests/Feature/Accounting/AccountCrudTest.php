<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    public function test_index_lists_company_accounts(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        app(ChartOfAccountsService::class)->seedDefaults($company, $user->id);

        $response = $this->getJson('/api/v1/accounts');

        $response->assertOk()
            ->assertJsonFragment(['code' => '1110', 'name' => 'Cash and Cash Equivalents'])
            ->assertJsonFragment(['code' => '4100', 'name' => 'Rental Income']);
    }

    public function test_store_creates_custom_account(): void
    {
        [$company] = $this->actingCompanyUser();

        $response = $this->postJson('/api/v1/accounts', [
            'code' => '5100',
            'name' => 'Maintenance Expense',
            'type' => Account::TYPE_EXPENSE,
            'status' => Account::STATUS_ACTIVE,
        ])->assertCreated();

        $response->assertJsonPath('data.code', '5100')
            ->assertJsonPath('data.type', Account::TYPE_EXPENSE)
            ->assertJsonPath('data.controls.can_delete', true);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $company->id,
            'code' => '5100',
            'name' => 'Maintenance Expense',
        ]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        app(ChartOfAccountsService::class)->seedDefaults($company, $user->id);

        $this->postJson('/api/v1/accounts', [
            'code' => '1110',
            'name' => 'Duplicate Cash',
            'type' => Account::TYPE_ASSET,
            'status' => Account::STATUS_ACTIVE,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_system_account_cannot_be_deleted(): void
    {
        [$company, $user] = $this->actingCompanyUser();
        app(ChartOfAccountsService::class)->seedDefaults($company, $user->id);

        $account = Account::query()
            ->where('company_id', $company->id)
            ->where('code', '1120')
            ->firstOrFail();

        $this->deleteJson("/api/v1/accounts/{$account->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'ACCOUNT_SYSTEM_PROTECTED');
    }

    public function test_custom_account_can_be_deleted(): void
    {
        [$company] = $this->actingCompanyUser();

        $create = $this->postJson('/api/v1/accounts', [
            'code' => '5999',
            'name' => 'Temp Account',
            'type' => Account::TYPE_EXPENSE,
            'status' => Account::STATUS_ACTIVE,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->deleteJson("/api/v1/accounts/{$id}")
            ->assertNoContent();

        $this->assertSoftDeleted('accounts', ['id' => $id]);
    }
}
