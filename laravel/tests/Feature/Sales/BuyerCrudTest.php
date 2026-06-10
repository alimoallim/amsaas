<?php

namespace Tests\Feature\Sales;

use App\Models\Buyer;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BuyerCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingCompanyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$company, $user];
    }

    public function test_can_create_list_show_update_and_delete_buyer(): void
    {
        [$company] = $this->actingCompanyUser();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $create = $this->postJson('/api/v1/buyers', [
            'full_name' => 'Amina Hassan',
            'email' => 'amina@example.com',
            'phone' => '+252611111111',
            'tenant_id' => $tenant->id,
            'country' => 'Somalia',
            'city' => 'Mogadishu',
        ])->assertCreated();

        $buyerId = $create->json('data.id');
        $this->assertNotEmpty($create->json('data.buyer_code'));

        $this->getJson('/api/v1/buyers')
            ->assertOk()
            ->assertJsonPath('data.0.full_name', 'Amina Hassan');

        $this->getJson("/api/v1/buyers/{$buyerId}")
            ->assertOk()
            ->assertJsonPath('data.tenant.id', $tenant->id);

        $this->putJson("/api/v1/buyers/{$buyerId}", [
            'full_name' => 'Amina H.',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.full_name', 'Amina H.')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/buyers/{$buyerId}")
            ->assertOk();

        $this->assertSoftDeleted('buyers', ['id' => $buyerId]);
    }

    public function test_buyer_isolation_between_companies(): void
    {
        [$companyA] = $this->actingCompanyUser();
        $buyer = Buyer::factory()->create(['company_id' => $companyA->id]);

        $companyB = Company::factory()->create();
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        Sanctum::actingAs($userB);

        $this->getJson("/api/v1/buyers/{$buyer->id}")
            ->assertNotFound();
    }
}
