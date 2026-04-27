<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuditLogController  (testing environments only)
 *
 * Exposes read-only and utility endpoints your automation tool can call to
 * inspect and reset audit log state without touching production code or
 * requiring an admin session.
 *
 * All endpoints are protected by the TestingEnvironmentOnly middleware which
 * aborts with 404 in production and enforces the optional X-Testing-Token
 * shared secret (see routes/testing.php and .env TESTING_TIME_TOKEN).
 *
 * Endpoints (all prefixed with /_testing/audit-log  –  see routes/testing.php):
 *
 *   GET  /              → list & filter log entries (paginated JSON)
 *   GET  /latest        → shortcut: most-recent N entries
 *   GET  /count         → summary counts (total, today, by category)
 *   POST /flush         → delete ALL audit log rows  (test teardown)
 *   GET  /flush         → same as POST /flush  (browser / URL-only tools)
 */
class AuditLogController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Apply every supported filter to the given query builder.
     * Kept in one place so /index and /latest share identical filter logic.
     */
    private function applyFilters(Request $request, \Illuminate\Database\Eloquent\Builder $query): void
    {
        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->get('search').'%');
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->get('log_name'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', (int) $request->get('causer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        if ($request->filled('entity')) {
            $query->where('properties->entity', $request->get('entity'));
        }

        if ($request->filled('description')) {
            $query->where('description', $request->get('description'));
        }
    }

    /** Serialize a single Activity row to an array safe for JSON output. */
    private function serializeEntry(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'properties' => $activity->properties,
            'batch_uuid' => $activity->batch_uuid,
            'sha256_hash' => $activity->sha256_hash,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────

    /**
     * GET /_testing/audit-log
     *
     * Query parameters (all optional):
     *   search      – substring match on description
     *   log_name    – exact match on log_name column
     *   causer_id   – integer causer ID
     *   date_from   – include entries on/after this date  (Y-m-d)
     *   date_to     – include entries on/before this date (Y-m-d)
     *   entity      – exact match on properties->entity JSON key
     *   description – exact match on description column
     *   per_page    – rows per page (default 25, max 200)
     *   page        – page number (default 1)
     *
     * Returns a paginated JSON collection of audit log entries.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'causer_id' => ['sometimes', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $perPage = $request->integer('per_page', 25);

        $query = Activity::query()->latest();
        $this->applyFilters($request, $query);

        $paginator = $query->with('causer')->paginate($perPage)->withQueryString();

        return response()->json([
            'message' => 'Audit log entries retrieved.',
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'data' => collect($paginator->items())->map(fn (Activity $a) => $this->serializeEntry($a)),
        ]);
    }

    /**
     * GET /_testing/audit-log/latest
     *
     * Query parameters (all optional, same filters as /index plus):
     *   limit  – number of entries to return (default 10, max 200)
     *
     * Shortcut for assertions like "assert the last emitted event was X".
     */
    public function latest(Request $request): JsonResponse
    {
        $request->validate([
            'causer_id' => ['sometimes', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = $request->integer('limit', 10);

        $query = Activity::query()->latest();
        $this->applyFilters($request, $query);

        $entries = $query->with('causer')->limit($limit)->get();

        return response()->json([
            'message' => 'Latest audit log entries retrieved.',
            'count' => $entries->count(),
            'data' => $entries->map(fn (Activity $a) => $this->serializeEntry($a)),
        ]);
    }

    /**
     * GET /_testing/audit-log/last
     *
     * Same filters as /index. Returns a single most-recent entry (or null).
     */
    public function last(Request $request): JsonResponse
    {
        $request->validate([
            'causer_id' => ['sometimes', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $query = Activity::query()->latest();
        $this->applyFilters($request, $query);

        $entry = $query->with('causer')->first();

        return response()->json([
            'message' => 'Last audit log entry retrieved.',
            'data' => $entry ? $this->serializeEntry($entry) : null,
        ]);
    }

    /**
     * GET /_testing/audit-log/count
     *
     * Returns summary counts useful for quick assertions:
     *   total, today, finance, auth, registration, and per-log_name breakdown.
     *
     * Accepts the same filter parameters as /index to count a filtered subset.
     */
    public function count(Request $request): JsonResponse
    {
        $request->validate([
            'causer_id' => ['sometimes', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        // Filtered count (respects any query params supplied).
        $filteredQuery = Activity::query();
        $this->applyFilters($request, $filteredQuery);

        // Category counts are always unfiltered totals so tests can assert
        // global state regardless of the current filter context.
        $logNameBreakdown = Activity::query()
            ->selectRaw('log_name, COUNT(*) as count')
            ->whereNotNull('log_name')
            ->groupBy('log_name')
            ->pluck('count', 'log_name');

        return response()->json([
            'message' => 'Audit log counts retrieved.',
            'filtered' => $filteredQuery->count(),
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', today())->count(),
            'finance' => Activity::where('description', 'like', 'finance.%')->count(),
            'auth' => Activity::where('description', 'like', 'auth.%')->count(),
            'registration' => Activity::where('description', 'like', 'registration.%')->count(),
            'by_log_name' => $logNameBreakdown,
        ]);
    }

    /**
     * POST /_testing/audit-log/flush
     * GET  /_testing/audit-log/flush
     *
     * Truncates the entire activity_log table.
     * Call this in your test teardown / after-all hook to guarantee a clean
     * slate for the next test.
     *
     * WARNING: This is intentionally destructive – only available in
     * non-production environments (enforced by TestingEnvironmentOnly middleware).
     */
    public function flush(): JsonResponse
    {
        $deleted = Activity::query()->delete();

        return response()->json([
            'message' => 'Audit log flushed. All entries have been deleted.',
            'deleted_count' => $deleted,
        ]);
    }
}
