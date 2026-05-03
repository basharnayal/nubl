<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApplyMockedTime
 *
 * Runs on EVERY request (registered in the global middleware stack).
 * In non-production environments it reads a persisted time value from the
 * cache and calls Carbon::setTestNow() so that the whole application
 * (models, jobs, scheduled commands, etc.) sees the mocked clock.
 * PHPUnit tests are excluded so their own Carbon::setTestNow() calls remain
 * isolated and deterministic.
 *
 * In production this middleware is a no-op – the environment guard at the
 * top of handle() returns immediately, and Carbon is never touched.
 */
class ApplyMockedTime
{
    /**
     * The cache key used to persist the mocked timestamp across requests.
     * Must match the constant defined in Testing\TimeController.
     */
    public const CACHE_KEY = 'testing:mocked_time';

    public function handle(Request $request, Closure $next): Response
    {
        // ── Hard production guard ─────────────────────────────────────────
        // Never apply mocked time in production or PHPUnit tests, no matter what is in cache.
        if (app()->environment(['production', 'testing'])) {
            return $next($request);
        }

        // ── Rehydrate mocked time offset from cache ───────────────────────
        // We store an offset in seconds rather than a frozen timestamp, so
        // the clock ticks normally but is always shifted by that amount.
        $offsetSeconds = Cache::get(self::CACHE_KEY);

        if ($offsetSeconds !== null) {
            Carbon::setTestNow(Carbon::now()->addSeconds((int) $offsetSeconds));
        } else {
            // Ensure any previously set test-now is cleared when no mock is active.
            Carbon::setTestNow(null);
        }

        return $next($request);
    }
}
