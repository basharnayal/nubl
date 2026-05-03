<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ApplyMockedTime;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * TimeController  (testing environments only)
 *
 * Exposes four endpoints your automation tool can call to control the
 * application clock without touching production code.
 *
 * All state is stored in the Laravel cache under the key defined in
 * ApplyMockedTime::CACHE_KEY so that every subsequent HTTP request sees
 * the same mocked time via the ApplyMockedTime global middleware.
 *
 * Endpoints (all prefixed with /_testing/time  –  see routes/testing.php):
 *
 *   GET  /          → show current (possibly mocked) time
 *   POST /set       → set an absolute datetime
 *   POST /advance   → advance time by a given duration
 *   POST /reset     → clear the mock and return to real time
 */
class TimeController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function storedOffsetSeconds(): ?int
    {
        $raw = Cache::get(ApplyMockedTime::CACHE_KEY);

        return $raw !== null ? (int) $raw : null;
    }

    private function persistOffset(int $offsetSeconds): void
    {
        Cache::forever(ApplyMockedTime::CACHE_KEY, $offsetSeconds);
        // Also apply immediately to this request's Carbon instance.
        Carbon::setTestNow(Carbon::now()->addSeconds($offsetSeconds));
    }

    private function timePayload(?int $offsetSeconds = null): array
    {
        $realNow = Carbon::createFromTimestamp(time());
        $mockedNow = $offsetSeconds !== null ? $realNow->copy()->addSeconds($offsetSeconds) : null;

        return [
            'is_mocked'      => $offsetSeconds !== null,
            'offset_seconds' => $offsetSeconds,
            'mocked_time'    => $mockedNow?->toIso8601String(),
            'real_time'      => $realNow->toIso8601String(),
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────

    /**
     * GET /_testing/time
     *
     * Returns the current application time and whether it is mocked.
     */
    public function show(): JsonResponse
    {
        $offset = $this->storedOffsetSeconds();

        return response()->json([
            'message' => $offset !== null
                ? "Time is offset by {$offset} seconds (clock is running)."
                : 'Time is running normally (no mock active).',
            ...$this->timePayload($offset),
        ]);
    }

    /**
     * POST /_testing/time/set
     *
     * Body (JSON):
     *   { "datetime": "2025-12-31 23:59:00" }
     *
     * Sets the application clock offset so that "now" appears to be the
     * given datetime. The clock continues ticking from that point.
     */
    public function set(Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => ['required', 'date'],
        ]);

        $target = Carbon::parse($request->input('datetime'));
        $offsetSeconds = (int) $target->diffInSeconds(Carbon::now(), false) * -1;
        $this->persistOffset($offsetSeconds);

        return response()->json([
            'message' => 'Application time set successfully.',
            ...$this->timePayload($offsetSeconds),
        ]);
    }

    /**
     * POST /_testing/time/advance
     *
     * Body (JSON, all fields optional, at least one required):
     *   {
     *     "years": 0, "months": 0, "weeks": 0,
     *     "days":  1, "hours":  0, "minutes": 0, "seconds": 0
     *   }
     *
     * Advances the mocked clock (or real time as baseline if no mock is
     * active yet) by the supplied duration.  Useful for simulating
     * subscription renewals, token expiry, reminder windows, etc.
     */
    public function advance(Request $request): JsonResponse
    {
        $request->validate([
            'seconds' => ['sometimes', 'integer', 'min:0'],
            'minutes' => ['sometimes', 'integer', 'min:0'],
            'hours' => ['sometimes', 'integer', 'min:0'],
            'days' => ['sometimes', 'integer', 'min:0'],
            'weeks' => ['sometimes', 'integer', 'min:0'],
            'months' => ['sometimes', 'integer', 'min:0'],
            'years' => ['sometimes', 'integer', 'min:0'],
        ]);

        // At least one unit must be supplied.
        $units = ['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'];
        if (! collect($units)->contains(fn ($u) => $request->has($u))) {
            return response()->json([
                'message' => 'Provide at least one time unit to advance (seconds, minutes, hours, days, weeks, months, years).',
            ], 422);
        }

        // Calculate additional offset from the requested duration.
        $additionalSeconds = 0;
        $additionalSeconds += $request->integer('seconds', 0);
        $additionalSeconds += $request->integer('minutes', 0) * 60;
        $additionalSeconds += $request->integer('hours', 0) * 3600;
        $additionalSeconds += $request->integer('days', 0) * 86400;
        $additionalSeconds += $request->integer('weeks', 0) * 604800;
        // For months/years, compute from a reference point to handle variable lengths.
        if ($request->has('months') || $request->has('years')) {
            $ref = Carbon::now();
            $shifted = $ref->copy()
                ->addMonths($request->integer('months', 0))
                ->addYears($request->integer('years', 0));
            $additionalSeconds += (int) $shifted->diffInSeconds($ref, false) * -1;
        }

        // Accumulate on top of any existing offset.
        $currentOffset = $this->storedOffsetSeconds() ?? 0;
        $newOffset = $currentOffset + $additionalSeconds;

        $this->persistOffset($newOffset);

        return response()->json([
            'message' => 'Application time advanced successfully.',
            ...$this->timePayload($newOffset),
        ]);
    }

    /**
     * POST /_testing/time/reset
     *
     * Clears the time offset.  Subsequent requests will use the real system clock.
     * Call this in your test teardown / after-all hook.
     */
    public function reset(): JsonResponse
    {
        Cache::forget(ApplyMockedTime::CACHE_KEY);
        Carbon::setTestNow(null);

        return response()->json([
            'message' => 'Time offset cleared. Application is using the real clock.',
            'real_time' => Carbon::createFromTimestamp(time())->toIso8601String(),
        ]);
    }
}
