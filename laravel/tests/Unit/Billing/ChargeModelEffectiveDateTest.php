<?php

namespace Tests\Unit\Billing;

use App\Models\ChargeModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargeModelEffectiveDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_effective_on_same_calendar_day_as_effective_from(): void
    {
        Carbon::setTestNow('2026-06-07 14:30:00');

        $model = ChargeModel::factory()->make([
            'effective_from' => '2026-06-07',
            'effective_to' => null,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $this->assertTrue($model->isCurrentlyEffective());
    }

    public function test_model_not_effective_before_effective_from(): void
    {
        Carbon::setTestNow('2026-06-06 12:00:00');

        $model = ChargeModel::factory()->make([
            'effective_from' => '2026-06-07',
            'effective_to' => null,
            'status' => ChargeModel::STATUS_ACTIVE,
        ]);

        $this->assertFalse($model->isCurrentlyEffective());
    }
}
