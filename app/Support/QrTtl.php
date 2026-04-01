<?php

namespace App\Support;

use App\Models\SystemSetting;

/**
 * Effective QR token TTL: DB override (FR-8.3) or config default (FR-8.2).
 */
class QrTtl
{
    public static function currentMinutes(): int
    {
        $raw = SystemSetting::getValue('qr.ttl_minutes');
        if ($raw === null || $raw === '') {
            return (int) config('qr.ttl_minutes', 180);
        }

        $min = (int) config('qr.ttl_minutes_min', 15);
        $max = (int) config('qr.ttl_minutes_max', 720);

        return max($min, min($max, (int) $raw));
    }
}
