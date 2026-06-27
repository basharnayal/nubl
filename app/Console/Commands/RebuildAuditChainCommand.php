<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

/**
 * One-time command to back-fill previous_hash and re-seal sha256_hash on all
 * existing activity_log rows using the current HMAC chain algorithm.
 *
 * Run once after deploying the 2026_06_27_000001 migration:
 *   php artisan audit:rebuild-chain
 *
 * Safety notes:
 *   - Uses updateQuietly() so no model events fire (creating/created are skipped).
 *   - Uses withoutTimestamps() so updated_at is not touched.
 *   - Idempotent: safe to run again if interrupted; it will re-seal from scratch.
 *   - After this runs the chain is trusted from this point forward. It does NOT
 *     prove rows were unmodified BEFORE the reseal — that window is a known,
 *     documented limitation of adopting chaining retrospectively.
 */
class RebuildAuditChainCommand extends Command
{
    protected $signature = 'audit:rebuild-chain
                            {--chunk=500 : Rows to process per database query}
                            {--dry-run   : Show what would change without writing}';

    protected $description = 'Back-fill previous_hash and re-seal sha256_hash on all activity_log rows (run once after the previous_hash migration).';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun    = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[dry-run] No rows will be written.');
        }

        $total    = Activity::count();
        $resealed = 0;
        $skipped  = 0;
        $prev     = Activity::GENESIS_HASH;

        $this->info("Rebuilding audit chain for {$total} row(s)…");

        Activity::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$prev, &$resealed, &$skipped, $dryRun): void {
                foreach ($rows as $row) {
                    $newPreviousHash = $prev;
                    $row->previous_hash = $newPreviousHash;
                    $newHash = Activity::computeHashFor($row);

                    if ($newHash === $row->sha256_hash && $newPreviousHash === $row->getOriginal('previous_hash')) {
                        $prev = $newHash;
                        $skipped++;
                        continue;
                    }

                    if (! $dryRun) {
                        Activity::withoutTimestamps(function () use ($row, $newPreviousHash, $newHash): void {
                            $row->updateQuietly([
                                'previous_hash' => $newPreviousHash,
                                'sha256_hash'   => $newHash,
                            ]);
                        });
                    }

                    $prev = $newHash;
                    $resealed++;
                }
            });

        $verb = $dryRun ? 'Would reseal' : 'Resealed';
        $this->info("{$verb} {$resealed} row(s); {$skipped} already matched.");
        $this->info("Chain tip: {$prev}");

        return self::SUCCESS;
    }
}
