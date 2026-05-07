<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::query()->latest();

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

        $logs = $query->with('causer')->paginate(25)->withQueryString();

        $totalCount = Activity::count();
        $todayCount = Activity::whereDate('created_at', today())->count();
        $financeCount = Activity::where('description', 'like', 'finance.%')->count();
        $authCount = Activity::where('description', 'like', 'auth.%')->count();
        $registrationCount = Activity::where('description', 'like', 'registration.%')->count();

        $logNames = Activity::select('log_name')
            ->distinct()
            ->pluck('log_name')
            ->filter()
            ->values();

        return view('admin.audit-logs.index', compact(
            'logs',
            'totalCount',
            'todayCount',
            'financeCount',
            'authCount',
            'registrationCount',
            'logNames',
        ));
    }

    public function verify(Activity $log): JsonResponse
    {
        $computed = Activity::computeHashFor($log);
        $stored   = $log->sha256_hash;

        return response()->json([
            'verified' => $computed === $stored,
            'computed' => $computed,
            'stored'   => $stored,
        ]);
    }
}
