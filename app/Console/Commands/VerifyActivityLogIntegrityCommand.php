<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

/**
 * FR-13.2: Scheduled tamper-detection command.
 *
 * Walks every activity_log row in id-ascending order and checks two properties:
 *
 *   1. Content integrity — recompute HMAC from stored fields; must match sha256_hash.
 *      Catches: field-level edits to any row.
 *
 *   2. Chain linkage — each row's previous_hash must equal the preceding row's
 *      sha256_hash (or GENESIS_HASH for the first row).
 *      Catches: deleted rows, inserted fake rows, reordered rows.
 *
 * Rows with NULL sha256_hash are legacy pre-migration entries and are skipped
 * with a warning. Run audit:rebuild-chain to seal them.
 *
 * Exit codes:
 *   0 = chain intact
 *   1 = one or more integrity or linkage failures detected
 */
class VerifyActivityLogIntegrityCommand extends Command
{
    protected $signature = 'audit:verify-activity-hashes
                            {--chunk=500 : Number of rows to process per database chunk}';

    protected $description = 'Verify activity_log HMAC hashes and hash chain linkage (tamper detection).';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        $nullCount = Activity::query()->whereNull('sha256_hash')->count();
        if ($nullCount > 0) {
            $this->warn("{$nullCount} row(s) with NULL sha256_hash skipped (run audit:rebuild-chain to seal them).");
        }

        $verified       = 0;
        $contentBreaks  = [];   // IDs where recomputed hash ≠ stored sha256_hash
        $chainBreaks    = [];   // IDs where previous_hash ≠ preceding row's sha256_hash
        $expectedPrev   = Activity::GENESIS_HASH;
        $tip            = Activity::GENESIS_HASH;

        Activity::query()
            ->whereNotNull('sha256_hash')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (
                &$verified, &$contentBreaks, &$chainBreaks, &$expectedPrev, &$tip
            ): void {
                foreach ($rows as $row) {
                    // 1. Content integrity — did any field change?
                    $recomputed = Activity::computeHashFor($row);
                    if (! hash_equals($recomputed, (string) $row->sha256_hash)) {
                        $contentBreaks[] = $row->id;
                    }

                    // 2. Chain linkage — does this row link to the correct predecessor?
                    $storedPrev = (string) ($row->previous_hash ?? '');
                    if ($storedPrev === '') {
                        // previous_hash not yet populated — treat as chain break.
                        $chainBreaks[] = $row->id;
                    } elseif (! hash_equals($storedPrev, $expectedPrev)) {
                        $chainBreaks[] = $row->id;
                    }

                    // Advance the expected predecessor for the next row.
                    // Use the stored hash (not recomputed) so we walk the stored
                    // chain; content failures are reported separately.
                    $expectedPrev = (string) $row->sha256_hash;
                    $tip          = $expectedPrev;
                    $verified++;
                }
            });

        $this->info("Verified {$verified} row(s) with non-null sha256_hash.");
        $this->line("Chain tip: {$tip}");

        $failed = false;

        if ($contentBreaks !== []) {
            $failed = true;
            $this->error('Content tamper detected — stored hash does not match recomputed hash:');
            $this->line('IDs: '.implode(', ', array_slice($contentBreaks, 0, 50)));
            if (count($contentBreaks) > 50) {
                $this->line('… and '.(count($contentBreaks) - 50).' more.');
            }
        }

        if ($chainBreaks !== []) {
            $failed = true;
            $this->error('Chain break detected — possible row deletion, insertion, or reordering:');
            $this->line('IDs: '.implode(', ', array_slice($chainBreaks, 0, 50)));
            if (count($chainBreaks) > 50) {
                $this->line('… and '.(count($chainBreaks) - 50).' more.');
            }
        }

        if (! $failed) {
            $this->info('Chain intact — all hashes verified and all links valid.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
