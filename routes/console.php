<?php

use App\Console\Commands\GenerateSummaryReportCommand;
use App\Console\Commands\VerifyActivityLogIntegrityCommand;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Test-mode toggle: when config('scheduler.test_mode') is true, jobs marked
 * with $whenTesting() run hourly instead of their production cadence.
 * Read through config() so the value survives `php artisan config:cache`
 * (Forge runs this after every deploy). Toggle via SCHEDULER_TEST_MODE in .env,
 * then re-run config:cache (or redeploy on Forge).
 */
$testMode = (bool) config('scheduler.test_mode');

$whenTesting = function (Event $event, Closure $productionCadence) use ($testMode): Event {
    return $testMode ? $event->hourly() : $productionCadence($event);
};

// ==============================================================
// Forge scheduler heartbeat — proves the Laravel scheduler cron is alive.
Schedule::call(fn () => Http::get(config('services.forge.heartbeat_url')))
    ->hourly()
    ->name('forge-scheduler-heartbeat')
    ->withoutOverlapping();

// ==============================================================
//  Verify activity_log SHA-256 hashes (tamper detection).
Schedule::command(VerifyActivityLogIntegrityCommand::class)
    ->dailyAt('03:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/activity-log-integrity.log'));

// ==============================================================
// Weekly provider bank payout requests (Sun 00:00, app timezone — set APP_TIMEZONE=Asia/Riyadh for KSA).
$whenTesting(
    Schedule::command('provider-payouts:generate-weekly'),
    fn (Event $e) => $e->weeklyOn(Carbon::SUNDAY, '00:00'),
)
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/provider-payout-weekly.log'));

// ==============================================================
// FR-19.1: Weekly financial summary report (Sun 00:00, app timezone — set APP_TIMEZONE=Asia/Riyadh for KSA).
$whenTesting(
    Schedule::command(GenerateSummaryReportCommand::class, ['--type=weekly']),
    fn (Event $e) => $e->weeklyOn(Carbon::SUNDAY, '00:00'),
)
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-weekly.log'));

// ==============================================================
// Monthly financial summary report (1st of every month 06:00, covers previous calendar month).
$whenTesting(
    Schedule::command(GenerateSummaryReportCommand::class, ['--type=monthly']),
    fn (Event $e) => $e->monthlyOn(1, '06:00'),
)
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-monthly.log'));
