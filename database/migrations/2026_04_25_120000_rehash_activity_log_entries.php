<?php

use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;

/**
 * One-time migration to rehash all activity_log rows using the fixed
 * recursive-ksort algorithm, so that existing hashes match verification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Activity::query()
            ->whereNotNull('sha256_hash')
            ->chunkById(500, function ($activities): void {
                foreach ($activities as $activity) {
                    $newHash = Activity::computeHashFor($activity);
                    if ($newHash !== $activity->sha256_hash) {
                        Activity::withoutTimestamps(function () use ($activity, $newHash): void {
                            $activity->updateQuietly(['sha256_hash' => $newHash]);
                        });
                    }
                }
            });
    }

    public function down(): void
    {
        // Cannot reverse — original hashes are lost.
    }
};
