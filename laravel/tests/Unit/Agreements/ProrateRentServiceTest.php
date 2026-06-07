<?php

namespace Tests\Unit\Agreements;

use App\Services\Agreements\ProrateRentService;
use Carbon\Carbon;
use Tests\TestCase;

class ProrateRentServiceTest extends TestCase
{
    private ProrateRentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProrateRentService;
    }

    public function test_it_prorates_full_month_when_occupied_entire_period(): void
    {
        $start = Carbon::parse('2026-01-01');
        $end = Carbon::parse('2026-01-31');

        $amount = $this->service->prorate('3100.0000', $start, $end, $start, $end);

        $this->assertEquals('3100.0000', $amount);
    }

    public function test_it_prorates_half_month_when_occupancy_starts_mid_period(): void
    {
        $periodStart = Carbon::parse('2026-01-01');
        $periodEnd = Carbon::parse('2026-01-31');
        $occupancyStart = Carbon::parse('2026-01-16');

        $amount = $this->service->prorate(
            '3000.0000',
            $periodStart,
            $periodEnd,
            $occupancyStart,
            $periodEnd,
        );

        $this->assertGreaterThan(1400, (float) $amount);
        $this->assertLessThan(1600, (float) $amount);
    }

    public function test_it_returns_zero_when_occupancy_outside_period(): void
    {
        $amount = $this->service->prorate(
            '1000.0000',
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        $this->assertEquals('0.0000', $amount);
    }
}
