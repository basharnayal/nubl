<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Weekly settlement window for provider bank payouts.
 * Aligns with {@see WeeklyAllowanceSettings}: weeks run Sun–Sat in {@see config('app.timezone')}.
 *
 * When the scheduled job runs at Sunday 00:00, the "settled" week is the one that just ended
 * (previous Sun 00:00 through Sat end of day).
 */
final class ProviderPayoutWeek
{
    /**
     * @return array{week_start_at: Carbon, week_end_at: Carbon}
     */
    public static function settlementWeekBoundariesAt(Carbon $runAt): array
    {
        $tz = config('app.timezone');
        $at = $runAt->copy()->timezone($tz);

        $weekStart = $at->copy()->subWeek()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $at->copy()->subWeek()->endOfWeek(Carbon::SATURDAY)->endOfDay();

        return [
            'week_start_at' => $weekStart,
            'week_end_at' => $weekEnd,
        ];
    }
}
