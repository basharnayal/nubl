<?php

namespace App\Console\Commands;

use App\Services\ProviderPayoutGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyProviderPayoutsCommand extends Command
{
    protected $signature = 'provider-payouts:generate-weekly {--at= : ISO8601 datetime for testing (default: now)}';

    protected $description = 'Create weekly provider bank payout requests for unsettled internal earning credits.';

    public function handle(ProviderPayoutGenerationService $generationService): int
    {
        $at = $this->option('at')
            ? Carbon::parse((string) $this->option('at'))->timezone(config('app.timezone'))
            : Carbon::now()->timezone(config('app.timezone'));

        $ids = $generationService->generateWeeklyAt($at);

        $this->info('Created '.count($ids).' payout request(s).');
        if (count($ids) > 0) {
            $this->line('IDs: '.implode(', ', $ids));
        }

        return self::SUCCESS;
    }
}
