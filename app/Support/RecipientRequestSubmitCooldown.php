<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Blocks new recipient request submits for a short window after a FR-6.4 retry was queued,
 * so users cannot bypass the queued retry with a smaller cart or another provider.
 */
final class RecipientRequestSubmitCooldown
{
    private const KEY_PREFIX = 'recipient_request_submit_cooldown_until:';

    public static function start(int $userId, int $seconds): void
    {
        $seconds = max(1, $seconds);
        $until = Carbon::now()->addSeconds($seconds)->getTimestamp();
        Cache::put(self::KEY_PREFIX.$userId, $until, now()->addSeconds($seconds + 15));
    }

    public static function secondsRemaining(int $userId): int
    {
        $until = Cache::get(self::KEY_PREFIX.$userId);
        if (! is_numeric($until)) {
            return 0;
        }

        return max(0, (int) $until - Carbon::now()->getTimestamp());
    }

    public static function active(int $userId): bool
    {
        return self::secondsRemaining($userId) > 0;
    }

    public static function clear(int $userId): void
    {
        Cache::forget(self::KEY_PREFIX.$userId);
    }
}
