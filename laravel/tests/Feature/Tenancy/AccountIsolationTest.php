<?php

namespace Tests\Feature\Tenancy;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_excludes_other_company_accounts(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        Account::factory()->create([
            'company_id' => $companyA->id,
            'code' => '9001',
            'name' => 'Company A Account',
        ]);

        Account::factory()->create([
            'company_id' => $companyB->id,
            'code' => '9002',
            'name' => 'Company B Account',
        ]);

        Sanctum::actingAs($userA);

        $this->getJson('/api/v1/accounts')
            ->assertOk()
            ->assertJsonFragment(['code' => '9001'])
            ->assertJsonMissing(['code' => '9002']);
    }

    public function test_show_returns_not_found_for_other_company_account(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $accountB = Account::factory()->create([
            'company_id' => $companyB->id,
            'code' => '9002',
        ]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/accounts/{$accountB->id}")
            ->assertNotFound();
    }
}
