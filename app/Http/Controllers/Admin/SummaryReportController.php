<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SummaryReport;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FR-19.1: Admin view and download of auto-generated weekly / monthly reports.
 */
class SummaryReportController extends Controller
{
    public function __construct(
        private readonly \App\Services\Admin\SummaryReportExportService $exporter,
        private readonly AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reports.export_csv'), 403);

        $reports = SummaryReport::query()
            ->orderByDesc('generated_at')
            ->paginate(20);

        return view('admin.finances.summary-reports.index', compact('reports'));
    }

    public function download(Request $request, SummaryReport $summaryReport): StreamedResponse
    {
        abort_unless($request->user()->can('reports.export_csv'), 403);

        $this->auditService->log('summary_report', 'exported', [
            'decision' => 'download_xlsx',
            'summary_report_id' => $summaryReport->id,
            'type' => $summaryReport->type,
            'period_from' => $summaryReport->period_from?->toDateString(),
            'period_to' => $summaryReport->period_to?->toDateString(),
        ], $request->user()->id);

        $isAr     = app()->getLocale() === 'ar';
        $filename = $this->exporter->filename($summaryReport);

        return response()->streamDownload(function () use ($summaryReport, $isAr) {
            $spreadsheet = $this->exporter->build($summaryReport, $isAr);
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
