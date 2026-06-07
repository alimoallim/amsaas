<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_invoice_global_scope_hides_other_company_records_when_authenticated(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $invoiceB = MonthlyInvoice::factory()->create([
            'company_id' => $companyB->id,
        ]);

        Sanctum::actingAs($userA);

        $this->assertEquals(
            0,
            MonthlyInvoice::where('id', $invoiceB->id)->count(),
            'Company A user must not see Company B invoices via global scope.'
        );
    }
}
