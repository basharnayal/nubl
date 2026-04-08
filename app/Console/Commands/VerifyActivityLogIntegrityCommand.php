<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

/**
 * FR-13.2: Scheduled verification that stored `sha256_hash` values match a recomputation
 * from persisted fields (tamper detection after the fact).
 */
class VerifyActivityLogIntegrityCommand extends Command
{
    protected $signature = 'audit:verify-activity-hashes
                            {--chunk=500 : Number of rows to process per database chunk}';

    protected $description = 'Verify activity_log SHA-256 hashes match recomputed values (tamper detection).';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        $nullHashCount = Activity::query()->whereNull('sha256_hash')->count();
        if ($nullHashCount > 0) {
            $this->warn("{$nullHashCount} row(s) have NULL sha256_hash (legacy or pre-migration); skipped for mismatch check.");
        }

        $verified = 0;
        $mismatches = [];

        Activity::query()
            ->whereNotNull('sha256_hash')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($activities) use (&$verified, &$mismatches): void {
                foreach ($activities as $activity) {
                    $expected = Activity::computeHashFor($activity);
                    if (! hash_equals($expected, (string) $activity->sha256_hash)) {
                        $mismatches[] = $activity->id;
                    }
                    $verified++;
                }
            });

        $this->info("Verified {$verified} activity log row(s) with non-null sha256_hash.");

        if ($mismatches !== []) {
            $this->error('Hash mismatch detected — possible tampering or algorithm drift:');
            $ids = implode(', ', array_slice($mismatches, 0, 50));
            $this->line($ids);
            if (count($mismatches) > 50) {
                $this->line('… and '.(count($mismatches) - 50).' more.');
            }

            return self::FAILURE;
        }

        $this->info('All checked hashes match.');

        return self::SUCCESS;
    }
}
