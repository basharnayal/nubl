<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Locale-aware date/time for UI (12-hour clock, app timezone).
 */
final class UiDateTime
{
    /**
     * Example: "Sat, 5 April 2026 · 12:00 AM" (en) or localized month/day for ar.
     */
    public static function mediumWith12h(Carbon $dt): string
    {
        return $dt->copy()
            ->timezone(config('app.timezone'))
            ->locale(app()->getLocale())
            ->isoFormat('ddd, D MMMM YYYY · h:mm A');
    }
}
