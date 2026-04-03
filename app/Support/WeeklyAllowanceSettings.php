<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * FR-17.1: System-wide weekly allowance keys in system_settings.
 * Scheduled changes apply from the start of the next Sun–Sat week (fair to recipients).
 */
final class WeeklyAllowanceSettings
{
    public const KEY_ACTIVE = 'recipient.weekly_allowance_limit';

    public const KEY_PENDING_VALUE = 'recipient.weekly_allowance_pending_value';

    public const KEY_PENDING_EFFECTIVE_AT = 'recipient.weekly_allowance_pending_effective_at';

    /**
     * Next Sunday 00:00 (start of the week after the current allowance week).
     */
    public static function nextEffectiveBoundary(): Carbon
    {
        return Carbon::now()
            ->timezone(config('app.timezone'))
            ->startOfWeek(Carbon::SUNDAY)
            ->addWeek();
    }

    public static function clearPending(): void
    {
        DB::table('system_settings')->whereIn('key', [
            self::KEY_PENDING_VALUE,
            self::KEY_PENDING_EFFECTIVE_AT,
        ])->delete();
    }
}
