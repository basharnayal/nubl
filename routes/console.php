<?php

use App\Console\Commands\GenerateSummaryReportCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FR-19.1: Auto-generate financial summary reports on a schedule.
// Weekly report: every Monday at 06:00 (covers previous Mon–Sun).
// Monthly report: 1st of every month at 06:00 (covers previous calendar month).
Schedule::command(GenerateSummaryReportCommand::class, ['--type=weekly'])
    ->weeklyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-weekly.log'));

Schedule::command(GenerateSummaryReportCommand::class, ['--type=monthly'])
    ->monthlyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-monthly.log'));
