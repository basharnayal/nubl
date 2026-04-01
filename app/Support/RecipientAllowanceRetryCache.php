<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * FR-6.4: Pending recipient submit when weekly allowance was temporarily exceeded.
 */
class RecipientAllowanceRetryCache
{
    private const PAYLOAD_PREFIX = 'recipient_allowance_retry_payload:';

    private const JOB_LOCK_PREFIX = 'recipient_allowance_retry_job_lock:';

    /**
     * @param  array{provider_id: int, items: array<int, array{id: int, quantity: int}>}  $payload
     */
    public static function storePayload(int $userId, array $payload): void
    {
        Cache::put(self::PAYLOAD_PREFIX.$userId, $payload, now()->addMinutes(15));
    }

    /**
     * @return array{provider_id: int, items: array<int, array{id: int, quantity: int}>}|null
     */
    public static function getPayload(int $userId): ?array
    {
        $v = Cache::get(self::PAYLOAD_PREFIX.$userId);

        return is_array($v) ? $v : null;
    }

    public static function clear(int $userId): void
    {
        Cache::forget(self::PAYLOAD_PREFIX.$userId);
        Cache::forget(self::JOB_LOCK_PREFIX.$userId);
    }

    /**
     * Only one delayed retry job per lock window (payload can still be updated).
     */
    public static function tryScheduleJobLock(int $userId, int $lockSeconds): bool
    {
        return Cache::add(self::JOB_LOCK_PREFIX.$userId, true, $lockSeconds);
    }
}
