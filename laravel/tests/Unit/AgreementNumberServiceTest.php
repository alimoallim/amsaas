<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Services\AgreementNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocate_increments_after_existing_global_number(): void
    {
        Agreement::factory()->create([
            'agreement_number' => 'RA-2026-00001',
            'agreement_type' => Agreement::TYPE_RENTAL,
        ]);

        $next = AgreementNumberService::allocate(Agreement::TYPE_RENTAL);

        $this->assertSame('RA-2026-00002', $next);
    }
}
