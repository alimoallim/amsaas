<?php

namespace Tests\Feature\Billing;

use App\Models\Company;
use App\Services\Billing\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocates_sequential_numbers_per_company_and_year(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Properties']);

        $service = app(InvoiceNumberService::class);

        $first = $service->next($company->id, 2026);
        $second = $service->next($company->id, 2026);

        $this->assertEquals('ACME-2026-00001', $first);
        $this->assertEquals('ACME-2026-00002', $second);

        $this->assertDatabaseHas('invoice_number_sequences', [
            'company_id' => $company->id,
            'year' => 2026,
            'last_number' => 2,
        ]);
    }

    public function test_numbers_are_isolated_per_company(): void
    {
        $companyA = Company::factory()->create(['name' => 'Alpha']);
        $companyB = Company::factory()->create(['name' => 'Beta']);

        $service = app(InvoiceNumberService::class);

        $this->assertEquals('ALPHA-2026-00001', $service->next($companyA->id, 2026));
        $this->assertEquals('BETA-2026-00001', $service->next($companyB->id, 2026));
    }

    public function test_sequence_resets_per_calendar_year(): void
    {
        $company = Company::factory()->create(['name' => 'AMS']);
        $service = app(InvoiceNumberService::class);

        $this->assertEquals('AMS-2026-00001', $service->next($company->id, 2026));
        $this->assertEquals('AMS-2027-00001', $service->next($company->id, 2027));
    }

    public function test_concurrent_allocation_produces_unique_numbers(): void
    {
        $company = Company::factory()->create(['name' => 'Concurrent Co']);
        $service = app(InvoiceNumberService::class);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $service->next($company->id, 2026);
        }

        $this->assertCount(10, array_unique($numbers));
        $this->assertEquals('CONCUR-2026-00010', end($numbers));
    }
}
