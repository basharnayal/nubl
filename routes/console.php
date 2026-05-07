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

// FR-19.1: Auto-generate financial summary reports on a schedule.
// Weekly report: every Monday at 06:00 (covers previous Mon–Sun).
// Monthly report: 1st of every month at 06:00 (covers previous calendar month).
// Schedule::command(GenerateSummaryReportCommand::class, ['--type=weekly'])
//     ->weeklyOn(1, '06:00')
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/report-weekly.log'));

// Schedule::command(GenerateSummaryReportCommand::class, ['--type=monthly'])
//     ->monthlyOn(1, '06:00')
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/report-monthly.log'));

// FR-13.2: Verify activity_log SHA-256 hashes (tamper detection).
Schedule::command(VerifyActivityLogIntegrityCommand::class)
    ->dailyAt('03:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/activity-log-integrity.log'));

// // Weekly provider bank payout requests (Sun 00:00, app timezone — set APP_TIMEZONE=Asia/Riyadh for KSA).
// Schedule::command('provider-payouts:generate-weekly')
//     ->weeklyOn(Carbon::SUNDAY, '00:00')
//     ->timezone(config('app.timezone'))
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/provider-payout-weekly.log'));

// Forge scheduler heartbeat — proves the Laravel scheduler cron is alive.
// Forge setting: every hour, notify after 5 minutes.
Schedule::call(fn () => Http::get(config('services.forge.heartbeat_url')))
    ->hourly()
    ->name('forge-scheduler-heartbeat')
    ->withoutOverlapping();

// For Testing 
// Every 2 minutes for local testing
// Schedule::command('provider-payouts:generate-weekly')
//     ->everyTwoMinutes()
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/provider-payout-weekly.log'));

// Weekly provider bank payout requests (Sun 00:00, app timezone — set APP_TIMEZONE=Asia/Riyadh for KSA).
Schedule::command('provider-payouts:generate-weekly')
    ->weeklyOn(Carbon::SUNDAY, '00:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/provider-payout-weekly.log'));


 Schedule::command(GenerateSummaryReportCommand::class, ['--type=weekly'])
    ->weeklyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-weekly.log'));

Schedule::command(GenerateSummaryReportCommand::class, ['--type=monthly'])
    ->monthlyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-monthly.log'));