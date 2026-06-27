<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = $this->buildFilterQuery($request);

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

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildFilterQuery($request);
        $query->with('causer')->reorder()->orderBy('id');

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        $this->auditService->log('audit', 'audit_logs_exported', [
            'decision' => 'export',
            'export_type' => 'audit_logs_csv',
            'filters' => $request->query(),
        ]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id',
                'log_name',
                'description',
                'entity',
                'causer_id',
                'causer_name',
                'causer_email',
                'ip',
                'user_agent',
                'previous_hash',
                'sha256_hash',
                'created_at',
            ]);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        $log->id,
                        $log->log_name,
                        $log->description,
                        $log->properties['entity'] ?? '',
                        $log->causer_id,
                        $log->causer?->name,
                        $log->causer?->email,
                        $log->properties['ip'] ?? '',
                        $log->properties['user_agent'] ?? '',
                        $log->previous_hash,
                        $log->sha256_hash,
                        $log->created_at?->toIso8601String(),
                    ]);
                }
            });
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $query = $this->buildFilterQuery($request);
        $query->with('causer')->reorder()->orderBy('id');

        $logs = $query->get();
        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.pdf';

        $this->auditService->log('audit', 'audit_logs_exported', [
            'decision' => 'export',
            'export_type' => 'audit_logs_pdf',
            'filters' => $request->query(),
        ]);

        $pdf = Pdf::loadView('admin.audit-logs.exports.audit-logs-pdf', [
            'logs' => $logs,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape');

        $content = $pdf->output();

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-SHA256' => hash('sha256', $content),
        ]);
    }

    public function verify(Activity $log): JsonResponse
    {
        // 1. Content integrity — does the row's hash match its fields?
        $computed       = Activity::computeHashFor($log);
        $stored         = (string) $log->sha256_hash;
        $contentVerified = hash_equals($computed, $stored);

        // 2. Chain linkage — does previous_hash equal the preceding row's sha256_hash?
        $preceding            = Activity::where('id', '<', $log->id)->orderByDesc('id')->first();
        $expectedPreviousHash = $preceding?->sha256_hash ?? Activity::GENESIS_HASH;
        $storedPreviousHash   = (string) ($log->previous_hash ?? '');
        $chainVerified        = $storedPreviousHash !== '' && hash_equals($storedPreviousHash, $expectedPreviousHash);

        return response()->json([
            'verified'              => $contentVerified && $chainVerified,
            'content_verified'      => $contentVerified,
            'chain_verified'        => $chainVerified,
            'computed'              => $computed,
            'stored'                => $stored,
            'previous_hash'         => $storedPreviousHash ?: null,
            'expected_previous_hash' => $expectedPreviousHash,
        ]);
    }

    private function buildFilterQuery(Request $request): Builder
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

        return $query;
    }
}
