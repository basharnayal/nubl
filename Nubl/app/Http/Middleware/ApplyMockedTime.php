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
        // Never apply mocked time in production, no matter what is in cache.
        if (app()->environment('production')) {
            return $next($request);
        }

        // ── Rehydrate mocked time from cache ──────────────────────────────
        $stored = Cache::get(self::CACHE_KEY);

        if ($stored !== null) {
            Carbon::setTestNow(Carbon::parse($stored));
        } else {
            // Ensure any previously set test-now is cleared when no mock is active.
            Carbon::setTestNow(null);
        }

        return $next($request);
    }
}
