<?php

namespace Tests\Unit\Recipient;

use App\Http\Services\RecipientAllowanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Config and date math; RefreshDatabase needed because weeklyLimit() reads system_settings.
 */
class RecipientAllowanceServiceConfigTest extends TestCase
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

        $this->assertSame(350.0, RecipientAllowanceService::weeklyLimit());
    }

    #[Test]
    public function current_week_bounds_span_sunday_through_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-07 12:00:00', 'UTC'));

        [$start, $end] = RecipientAllowanceService::getCurrentWeekBounds();

        $this->assertSame(Carbon::SUNDAY, $start->dayOfWeek);
        $this->assertSame(Carbon::SATURDAY, $end->dayOfWeek);
        $this->assertTrue($start->isSunday());
        $this->assertTrue($end->isSaturday());
        $this->assertTrue($start->lte($end));
    }
}
