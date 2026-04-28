<?php

namespace Tests\Unit\Services\Admin;

use App\Models\SummaryReport;
use App\Services\Admin\SummaryReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SummaryReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function filename_contains_report_type_and_period_dates(): void
    {
        $report = new SummaryReport([
            'type' => SummaryReport::TYPE_WEEKLY,
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-07',
            'payload' => [],
            'generated_at' => now(),
        ]);

        $filename = (new SummaryReportExportService)->filename($report);

        $this->assertSame('nubl-report-weekly-2026-04-01_2026-04-07.xlsx', $filename);
    }

    #[Test]
    public function build_generates_styled_spreadsheet_with_expected_core_cells(): void
    {
        if (! extension_loaded('zip') || ! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $this->markTestSkipped('PhpSpreadsheet (and ext-zip) is required for SummaryReportExportService tests.');
        }

        $report = new SummaryReport([
            'type' => SummaryReport::TYPE_MONTHLY,
            'period_from' => '2026-03-01',
            'period_to' => '2026-03-31',
            'generated_at' => now(),
            'payload' => [
                'payments_total_count' => 10,
                'payments_succeeded_count' => 8,
                'payments_succeeded_amount' => 300,
                'payments_failed_count' => 2,
                'payments_failed_amount' => 20,
                'payments_pending_count' => 0,
                'payments_by_status' => [],
                'ledger_entries_count' => 5,
                'ledger_in_amount' => 200,
                'ledger_out_amount' => 250,
                'ledger_net_amount' => -50,
                'payouts_to_providers' => 30,
            ],
        ]);

        $spreadsheet = (new SummaryReportExportService)->build($report, false);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Summary Report', $sheet->getTitle());
        $this->assertSame('NUBL — Summary Report', $sheet->getCell('A1')->getValue());
        $this->assertSame('A4', $sheet->getFreezePane());
        $this->assertSame('Report Type', $sheet->getCell('A2')->getValue());

        // Ledger net cell should be highlighted as error when negative.
        $this->assertSame('FFE4E1', strtoupper($sheet->getStyle('D11')->getFill()->getStartColor()->getRGB()));
    }

    #[Test]
    public function build_in_arabic_mode_sets_worksheet_to_right_to_left(): void
    {
        if (! extension_loaded('zip') || ! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $this->markTestSkipped('PhpSpreadsheet (and ext-zip) is required for SummaryReportExportService tests.');
        }

        $report = new SummaryReport([
            'type' => SummaryReport::TYPE_WEEKLY,
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-07',
            'generated_at' => now(),
            'payload' => [],
        ]);

        $spreadsheet = (new SummaryReportExportService)->build($report, true);

        $this->assertTrue($spreadsheet->getActiveSheet()->getRightToLeft());
    }
}
