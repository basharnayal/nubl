<?php

namespace App\Http\Services\Admin;

use App\Models\SummaryReport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds the styled XLSX export for FR-19.1 summary reports.
 * Uses the NUBL brand palette defined in resources/css/app.css.
 */
class SummaryReportExportService
{
    // Set once per build() call; read by all private helpers
    private bool $isAr = false;

    // ── NUBL brand palette (from resources/css/app.css) ──────────────────────
    private const CLR_TITLE_BG     = '1E293B'; // --color-nubl-dark
    private const CLR_TITLE_FG     = 'FFFFFF';
    private const CLR_SECTION_BG   = '2563EB'; // --color-nubl-blue-600
    private const CLR_SECTION_FG   = 'FFFFFF';
    private const CLR_HEADER_BG    = 'DBEAFE'; // --color-nubl-blue-100
    private const CLR_HEADER_FG    = '1E293B'; // --color-nubl-dark
    private const CLR_META_BG      = 'F8FAFC'; // --color-nubl-bg
    private const CLR_ROW_WHITE    = 'FFFFFF';
    private const CLR_ROW_ALT      = 'EFF6FF'; // --color-nubl-blue-50

    // Semantic row accent colours
    private const CLR_SUCCESS_BG   = 'D1FAE5'; // --color-success tint  (#10b981)
    private const CLR_SUCCESS_FG   = '065F46';
    private const CLR_WARNING_BG   = 'FEF3C7'; // --color-warning tint  (#ff9800)
    private const CLR_WARNING_FG   = '92400E';
    private const CLR_ERROR_BG     = 'FFE4E1'; // --color-error tint    (#ff5724)
    private const CLR_ERROR_FG     = '9B1C1C';
    private const CLR_INFO_BG      = 'E0F2FE'; // --color-info tint     (#0ea5e9)
    private const CLR_INFO_FG      = '0C4A6E';
    private const CLR_TEAL_BG      = 'CCFBF1'; // --color-nubl-teal-100 (#14b8a6)
    private const CLR_TEAL_FG      = '134E4A';

    // ── Public API ────────────────────────────────────────────────────────────

    public function filename(SummaryReport $report): string
    {
        return sprintf(
            'nubl-report-%s-%s_%s.xlsx',
            $report->type,
            $report->period_from->toDateString(),
            $report->period_to->toDateString()
        );
    }

    public function build(SummaryReport $report, bool $isAr): Spreadsheet
    {
        $this->isAr = $isAr;
        $t    = fn (string $en, string $ar): string => $isAr ? $ar : $en;
        $p    = $report->payload;

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($t('Summary Report', 'التقرير الملخص'));

        if ($isAr) {
            $sheet->setRightToLeft(true);
        }

        $row = 1;
        $row = $this->writeTitleBar($sheet, $row, $t, $report);
        $row = $this->writeMetaBlock($sheet, $row, $t, $report);

        $row = $this->writeDonationsSection($sheet, $row, $t, $p);
        $row = $this->writeLedgerSection($sheet, $row, $t, $p);
        $row = $this->writeRequestsSection($sheet, $row, $t, $p);
        $row = $this->writeRedemptionsSection($sheet, $row, $t, $p);
        $row = $this->writeParticipationSection($sheet, $row, $t, $p);

        $this->applyColumnWidths($sheet);
        $sheet->freezePane('A4');

        return $spreadsheet;
    }

    // ── Section writers ───────────────────────────────────────────────────────

    private function writeTitleBar(Worksheet $sheet, int $row, callable $t, SummaryReport $report): int
    {
        $sheet->mergeCells("A{$row}:J{$row}");
        $sheet->setCellValue("A{$row}", $t('NUBL — Summary Report', 'نظام نبل — التقرير الملخص'));
        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::CLR_TITLE_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_TITLE_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(40);

        return $row + 1;
    }

    private function writeMetaBlock(Worksheet $sheet, int $row, callable $t, SummaryReport $report): int
    {
        $typeLabel = app()->getLocale() === 'ar'
            ? ($report->type === 'weekly' ? 'أسبوعي' : 'شهري')
            : ucfirst($report->type);

        $metaDefs = [
            [
                $t('Report Type', 'نوع التقرير'), $typeLabel,
                '',
                $t('Generated At', 'تاريخ الإنشاء'), $report->generated_at->format('Y-m-d H:i'),
            ],
            [
                $t('Period From', 'من تاريخ'), $report->period_from->toDateString(),
                '',
                $t('Period To', 'إلى تاريخ'), $report->period_to->toDateString(),
            ],
        ];

        foreach ($metaDefs as $metaRow) {
            $sheet->fromArray($metaRow, null, "A{$row}");
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_META_BG]],
            ]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("D{$row}")->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        return $row + 1; // +1 spacer
    }

    private function writeDonationsSection(Worksheet $sheet, int $row, callable $t, array $p): int
    {
        $row = $this->sectionHeading($sheet, $row, 10, $t('Donations (Gateway Payments)', 'التبرعات (المدفوعات عبر البوابة)'));
        $row = $this->headerRow($sheet, $row, [
            $t('Total Payments', 'إجمالي المدفوعات'),
            $t('Succeeded Count', 'عدد الناجحة'),
            $t('Succeeded Amount (SAR)', 'مبلغ الناجحة (ر.س)'),
            $t('Failed Count', 'عدد الفاشلة'),
            $t('Failed Amount (SAR)', 'مبلغ الفاشلة (ر.س)'),
            $t('Pending / Processing', 'معلقة / قيد المعالجة'),
        ]);

        $data = [
            $p['payments_total_count']      ?? 0,
            $p['payments_succeeded_count']  ?? 0,
            $p['payments_succeeded_amount'] ?? 0,
            $p['payments_failed_count']     ?? 0,
            $p['payments_failed_amount']    ?? 0,
            $p['payments_pending_count']    ?? 0,
        ];

        $sheet->fromArray($data, null, "A{$row}");
        $this->styleDataRow($sheet, $row, count($data), [3, 5]);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row++;

        // Payment status breakdown (sub-table)
        if (! empty($p['payments_by_status'])) {
            $row = $this->writePaymentBreakdown($sheet, $row, $t, $p['payments_by_status']);
        }

        return $row + 1; // spacer
    }

    /**
     * @param  list<array{status: string, cnt: int, total: float}>  $rows
     */
    private function writePaymentBreakdown(Worksheet $sheet, int $row, callable $t, array $rows): int
    {
        // Sub-heading (arrow on the reading-start side)
        $sheet->setCellValue("A{$row}", $t('  ↳ Breakdown by Status', 'تفصيل حسب الحالة ↲  '));
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'italic' => true, 'color' => ['rgb' => self::CLR_SECTION_BG]],
        ]);
        $row++;

        // Sub-header
        $sheet->fromArray(
            [$t('Status', 'الحالة'), $t('Count', 'العدد'), $t('Total (SAR)', 'الإجمالي (ر.س)')],
            null, "A{$row}"
        );
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::CLR_HEADER_FG]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_HEADER_BG]],
        ]);
        $row++;

        // Status data rows with semantic colour coding
        $statusColours = [
            'SUCCEEDED'  => [self::CLR_SUCCESS_BG, self::CLR_SUCCESS_FG],
            'FAILED'     => [self::CLR_ERROR_BG,   self::CLR_ERROR_FG],
            'PENDING'    => [self::CLR_WARNING_BG,  self::CLR_WARNING_FG],
            'PROCESSING' => [self::CLR_INFO_BG,     self::CLR_INFO_FG],
            'INITIATED'  => [self::CLR_INFO_BG,     self::CLR_INFO_FG],
        ];

        foreach ($rows as $r) {
            $status = strtoupper((string) $r['status']);
            [$bg, $fg] = $statusColours[$status] ?? [self::CLR_ROW_WHITE, self::CLR_HEADER_FG];

            $sheet->fromArray([$r['status'], $r['cnt'], $r['total']], null, "A{$row}");
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                'font' => ['color' => ['rgb' => $fg]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            ]);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("B{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        return $row;
    }

    private function writeLedgerSection(Worksheet $sheet, int $row, callable $t, array $p): int
    {
        $row = $this->sectionHeading($sheet, $row, 10, $t('Fund Ledger', 'سجل الصندوق'));
        $row = $this->headerRow($sheet, $row, [
            $t('Ledger Entries', 'إدخالات السجل'),
            $t('Total Money In (SAR)', 'إجمالي الوارد (ر.س)'),
            $t('Total Money Out (SAR)', 'إجمالي الصادر (ر.س)'),
            $t('Net Position (SAR)', 'الصافي (ر.س)'),
            $t('Payouts to Providers (SAR)', 'المدفوعات للمزودين (ر.س)'),
        ]);

        $net  = (float) ($p['ledger_net_amount'] ?? 0);
        $data = [
            $p['ledger_entries_count'] ?? 0,
            $p['ledger_in_amount']     ?? 0,
            $p['ledger_out_amount']    ?? 0,
            $net,
            $p['payouts_to_providers'] ?? 0,
        ];

        $sheet->fromArray($data, null, "A{$row}");
        $this->styleDataRow($sheet, $row, count($data), [2, 3, 4, 5]);

        // Colour Net Position cell: green if positive, red if negative
        $netCell = "D{$row}";
        if ($net > 0) {
            $sheet->getStyle($netCell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::CLR_SUCCESS_FG]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_SUCCESS_BG]],
            ]);
        } elseif ($net < 0) {
            $sheet->getStyle($netCell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::CLR_ERROR_FG]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_ERROR_BG]],
            ]);
        }

        $sheet->getRowDimension($row)->setRowHeight(20);
        return $row + 2; // +1 data row, +1 spacer
    }

    private function writeRequestsSection(Worksheet $sheet, int $row, callable $t, array $p): int
    {
        $row = $this->sectionHeading($sheet, $row, 10, $t('Requests', 'الطلبات'));
        $row = $this->headerRow($sheet, $row, [
            $t('Total', 'الإجمالي'),
            $t('Fulfilled', 'مكتملة'),
            $t('Fulfilled Amt (SAR)', 'مبلغ المكتملة (ر.س)'),
            $t('Approved', 'معتمدة'),
            $t('Redeemable', 'قابلة للاسترداد'),
            $t('Pending', 'معلقة'),
            $t('Rejected', 'مرفوضة'),
            $t('Cancelled', 'ملغاة'),
            $t('City Fund', 'صندوق المدينة'),
            $t('Provider Adoption', 'تبني المزود'),
        ]);

        $data = [
            $p['requests_total']            ?? 0,
            $p['requests_fulfilled']        ?? 0,
            $p['requests_fulfilled_amount'] ?? 0,
            $p['requests_approved']         ?? 0,
            $p['requests_redeemable']       ?? 0,
            $p['requests_pending']          ?? 0,
            $p['requests_rejected']         ?? 0,
            $p['requests_cancelled']        ?? 0,
            $p['requests_city_fund']        ?? 0,
            $p['requests_adopted']          ?? 0,
        ];

        $sheet->fromArray($data, null, "A{$row}");
        $this->styleDataRow($sheet, $row, count($data), [3]);

        // Semantic highlights on individual cells
        $this->accentCell($sheet, "B{$row}", self::CLR_TEAL_BG,   self::CLR_TEAL_FG);   // Fulfilled (teal)
        $this->accentCell($sheet, "C{$row}", self::CLR_TEAL_BG,   self::CLR_TEAL_FG);   // Fulfilled amount
        $this->accentCell($sheet, "G{$row}", self::CLR_ERROR_BG,  self::CLR_ERROR_FG);  // Rejected
        $this->accentCell($sheet, "H{$row}", self::CLR_WARNING_BG, self::CLR_WARNING_FG); // Cancelled

        $sheet->getRowDimension($row)->setRowHeight(20);
        return $row + 2;
    }

    private function writeRedemptionsSection(Worksheet $sheet, int $row, callable $t, array $p): int
    {
        $row = $this->sectionHeading($sheet, $row, 10, $t('QR Redemptions', 'استردادات رمز QR'));
        $row = $this->headerRow($sheet, $row, [
            $t('Total Codes', 'إجمالي الرموز'),
            $t('Redeemed', 'تم الاسترداد'),
            $t('Pending', 'معلقة'),
            $t('Expired', 'منتهية الصلاحية'),
        ]);

        $data = [
            $p['redemptions_total']    ?? 0,
            $p['redemptions_redeemed'] ?? 0,
            $p['redemptions_pending']  ?? 0,
            $p['redemptions_expired']  ?? 0,
        ];

        $sheet->fromArray($data, null, "A{$row}");
        $this->styleDataRow($sheet, $row, count($data), []);

        $this->accentCell($sheet, "B{$row}", self::CLR_TEAL_BG,   self::CLR_TEAL_FG);
        $this->accentCell($sheet, "C{$row}", self::CLR_WARNING_BG, self::CLR_WARNING_FG);
        $this->accentCell($sheet, "D{$row}", self::CLR_ERROR_BG,  self::CLR_ERROR_FG);

        $sheet->getRowDimension($row)->setRowHeight(20);
        return $row + 2;
    }

    private function writeParticipationSection(Worksheet $sheet, int $row, callable $t, array $p): int
    {
        $row = $this->sectionHeading($sheet, $row, 10, $t('Participation', 'المشاركة'));
        $row = $this->headerRow($sheet, $row, [
            $t('Active Providers (system-wide)', 'المزودون النشطون (على مستوى النظام)'),
            $t('Providers with Requests', 'مزودون لديهم طلبات'),
            $t('Recipients with Requests', 'مستفيدون لديهم طلبات'),
        ]);

        $data = [
            $p['active_providers_total']          ?? 0,
            $p['providers_with_requests']         ?? 0,
            $p['active_recipients_with_requests'] ?? 0,
        ];

        $sheet->fromArray($data, null, "A{$row}");
        $this->styleDataRow($sheet, $row, count($data), []);

        $sheet->getRowDimension($row)->setRowHeight(20);
        return $row + 2;
    }

    // ── Primitive helpers ─────────────────────────────────────────────────────

    /**
     * Write a full-width blue section heading row. Returns the next row number.
     */
    private function sectionHeading(Worksheet $sheet, int $row, int $totalCols, string $title): int
    {
        $lastCol = chr(64 + $totalCols);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");

        // Indent on the reading-start side: trailing spaces in RTL, leading in LTR
        $label = $this->isAr ? ($title . '  ') : ('  ' . $title);
        $sheet->setCellValue("A{$row}", $label);

        $hAlign = $this->isAr ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT;

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::CLR_SECTION_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_SECTION_BG]],
            'alignment' => ['horizontal' => $hAlign, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
        return $row + 1;
    }

    /**
     * Write a styled column header row. Returns the next row number.
     *
     * @param  list<string>  $headers
     */
    private function headerRow(Worksheet $sheet, int $row, array $headers): int
    {
        $lastCol = chr(64 + count($headers));
        $sheet->fromArray($headers, null, "A{$row}");
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => self::CLR_HEADER_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CLR_SECTION_BG]],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        return $row + 1;
    }

    /**
     * Apply white background, right-align numerics, and SAR format to a data row.
     *
     * @param  list<int>  $sarCols  1-indexed column positions holding SAR amounts
     */
    private function styleDataRow(Worksheet $sheet, int $row, int $colCount, array $sarCols): void
    {
        $lastCol = chr(64 + $colCount);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_ROW_WHITE]],
        ]);

        for ($c = 1; $c <= $colCount; $c++) {
            $cellRef = chr(64 + $c) . $row;
            $val     = $sheet->getCell($cellRef)->getValue();
            if (is_numeric($val)) {
                $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            if (in_array($c, $sarCols, true)) {
                $sheet->getStyle($cellRef)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
    }

    /**
     * Overlay a semantic background + foreground colour on a single cell,
     * preserving number format if already set.
     */
    private function accentCell(Worksheet $sheet, string $cellRef, string $bgRgb, string $fgRgb): void
    {
        $sheet->getStyle($cellRef)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $fgRgb]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgRgb]],
        ]);
    }

    private function applyColumnWidths(Worksheet $sheet): void
    {
        $widths = [32, 22, 26, 22, 26, 20, 20, 20, 20, 22];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
    }
}
