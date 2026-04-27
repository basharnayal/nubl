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

    private function storedTime(): ?Carbon
    {
        $raw = Cache::get(ApplyMockedTime::CACHE_KEY);

        return $raw ? Carbon::parse($raw) : null;
    }

    private function persist(Carbon $time): void
    {
        Cache::forever(ApplyMockedTime::CACHE_KEY, $time->toIso8601String());
        // Also apply immediately to this request's Carbon instance.
        Carbon::setTestNow($time);
    }

    private function timePayload(?Carbon $mocked = null): array
    {
        return [
            'is_mocked' => $mocked !== null,
            'mocked_time' => $mocked?->toIso8601String(),
            'real_time' => Carbon::createFromTimestamp(time())->toIso8601String(),
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
        $mocked = $this->storedTime();

        return response()->json([
            'message' => $mocked
                ? 'Time is currently mocked.'
                : 'Time is running normally (no mock active).',
            ...$this->timePayload($mocked),
        ]);
    }

    /**
     * POST /_testing/time/set
     *
     * Body (JSON):
     *   { "datetime": "2025-12-31 23:59:00" }
     *
     * Sets the application clock to an absolute datetime.
     */
    public function set(Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => ['required', 'date'],
        ]);

        $time = Carbon::parse($request->input('datetime'));
        $this->persist($time);

        return response()->json([
            'message' => 'Application time set successfully.',
            ...$this->timePayload($time),
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

        // Start from the current mock, falling back to real wall-clock time.
        $now = ($this->storedTime() ?? Carbon::now())->copy();

        $now->addSeconds($request->integer('seconds', 0));
        $now->addMinutes($request->integer('minutes', 0));
        $now->addHours($request->integer('hours', 0));
        $now->addDays($request->integer('days', 0));
        $now->addWeeks($request->integer('weeks', 0));
        $now->addMonths($request->integer('months', 0));
        $now->addYears($request->integer('years', 0));

        $this->persist($now);

        return response()->json([
            'message' => 'Application time advanced successfully.',
            ...$this->timePayload($now),
        ]);
    }

    /**
     * POST /_testing/time/reset
     *
     * Clears the time mock.  Subsequent requests will use the real system clock.
     * Call this in your test teardown / after-all hook.
     */
    public function reset(): JsonResponse
    {
        Cache::forget(ApplyMockedTime::CACHE_KEY);
        Carbon::setTestNow(null);

        return response()->json([
            'message' => 'Time mock cleared. Application is using the real clock.',
            'real_time' => Carbon::now()->toIso8601String(),
        ]);
    }
}
