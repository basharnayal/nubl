<?php

use App\Console\Commands\GenerateSummaryReportCommand;
use App\Console\Commands\VerifyActivityLogIntegrityCommand;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
Schedule::command('provider-payouts:generate-weekly')
    ->weeklyOn(Carbon::SUNDAY, '00:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/provider-payout-weekly.log'));

// ==============================================================
// FR-19.1: Weekly financial summary report (Sun 00:00, app timezone — set APP_TIMEZONE=Asia/Riyadh for KSA).
Schedule::command(GenerateSummaryReportCommand::class, ['--type=weekly'])
    ->weeklyOn(Carbon::SUNDAY, '00:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-weekly.log'));

// ==============================================================
// Monthly financial summary report (1st of every month 06:00, covers previous calendar month).
Schedule::command(GenerateSummaryReportCommand::class, ['--type=monthly'])
    ->monthlyOn(1, '06:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-monthly.log'));
