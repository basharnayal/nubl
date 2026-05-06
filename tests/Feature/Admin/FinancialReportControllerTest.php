<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Admin\FinancialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function daily_period_uses_current_day_bounds(): void
    {
        Carbon::setTestNow('2026-04-21 10:30:00');

        $service = Mockery::mock(FinancialService::class);
        $service->shouldReceive('getRangeSummary')
            ->once()
            ->andReturn($this->fakeSummary(
                Carbon::parse('2026-04-21 00:00:00'),
                Carbon::parse('2026-04-21 23:59:59')
            ));
        $this->app->instance(FinancialService::class, $service);

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.finances.reports.index', [
            'period' => 'daily',
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.finances.reports.index');
        $response->assertViewHas('period', 'daily');
        $response->assertViewHas('date_from', '2026-04-21');
        $response->assertViewHas('date_to', '2026-04-21');
        $response->assertViewHas('summary', fn (array $summary): bool => isset($summary['from'], $summary['to']));

        Carbon::setTestNow();
    }

    #[Test]
    public function monthly_period_uses_current_month_bounds(): void
    {
        Carbon::setTestNow('2026-04-21 10:30:00');

        $service = Mockery::mock(FinancialService::class);
        $service->shouldReceive('getRangeSummary')
            ->once()
            ->andReturn($this->fakeSummary(
                Carbon::parse('2026-04-01 00:00:00'),
                Carbon::parse('2026-04-30 23:59:59')
            ));
        $this->app->instance(FinancialService::class, $service);

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.finances.reports.index', [
            'period' => 'monthly',
        ]));

        $response->assertOk();
        $response->assertViewHas('period', 'monthly');
        $response->assertViewHas('date_from', '2026-04-01');
        $response->assertViewHas('date_to', '2026-04-30');
        $response->assertViewHas('summary', fn (array $summary): bool => isset($summary['from'], $summary['to']));

        Carbon::setTestNow();
    }

    #[Test]
    public function custom_period_without_dates_defaults_to_last_thirty_days(): void
    {
        Carbon::setTestNow('2026-04-21 10:30:00');

        $service = Mockery::mock(FinancialService::class);
        $service->shouldReceive('getRangeSummary')
            ->once()
            ->andReturn($this->fakeSummary(
                Carbon::parse('2026-03-22 00:00:00'),
                Carbon::parse('2026-04-21 23:59:59')
            ));
        $this->app->instance(FinancialService::class, $service);

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.finances.reports.index'));

        $response->assertOk();
        $response->assertViewHas('period', 'custom');
        $response->assertViewHas('date_from', '2026-03-22');
        $response->assertViewHas('date_to', '2026-04-21');
        $response->assertViewHas('summary', fn (array $summary): bool => isset($summary['from'], $summary['to']));

        Carbon::setTestNow();
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function fakeSummary(Carbon $from, Carbon $to): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'payments_total_count' => 1,
            'payments_succeeded_amount' => 10.0,
            'payments_failed_amount' => 0.0,
            'payments_by_status' => collect([
                (object) ['status' => 'SUCCEEDED', 'cnt' => 1, 'total' => 10.0],
            ]),
            'ledger_entries_count' => 1,
            'ledger_in_amount' => 10.0,
            'ledger_out_amount' => 0.0,
        ];
    }
}
