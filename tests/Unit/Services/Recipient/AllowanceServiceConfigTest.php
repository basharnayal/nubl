<?php

namespace Tests\Unit\Services\Recipient;

use App\Services\Recipient\AllowanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Config and date math; RefreshDatabase needed because weeklyLimit() reads system_settings.
 */
class AllowanceServiceConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function weekly_limit_reads_config_value(): void
    {
        config(['recipient.weekly_allowance_limit' => 350]);

        $this->assertSame(350.0, AllowanceService::weeklyLimit());
    }

    #[Test]
    public function current_week_bounds_span_sunday_through_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-07 12:00:00', 'UTC'));

        [$start, $end] = AllowanceService::getCurrentWeekBounds();

        $this->assertSame(Carbon::SUNDAY, $start->dayOfWeek);
        $this->assertSame(Carbon::SATURDAY, $end->dayOfWeek);
        $this->assertTrue($start->isSunday());
        $this->assertTrue($end->isSaturday());
        $this->assertTrue($start->lte($end));
    }
}
