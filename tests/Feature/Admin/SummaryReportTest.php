<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\GenerateSummaryReportCommand;
use App\Models\SummaryReport;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FR-19.1: Auto-generated weekly and monthly summary reports.
 */
class SummaryReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'status'    => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    // -------------------------------------------------------------------------
    // Command: weekly report
    // -------------------------------------------------------------------------

    #[Test]
    public function generate_weekly_report_command_creates_db_row_fr_19_1(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-07 06:00:00')); // Monday

        $this->artisan(GenerateSummaryReportCommand::class, ['--type' => 'weekly'])
            ->assertSuccessful();

        $this->assertDatabaseCount('summary_reports', 1);

        $report = SummaryReport::first();
        $this->assertSame('weekly', $report->type);
        $this->assertNotNull($report->payload);
        $this->assertArrayHasKey('payments_total_count', $report->payload);
        $this->assertArrayHasKey('ledger_in_amount', $report->payload);
        $this->assertNotNull($report->generated_at);
    }

    // -------------------------------------------------------------------------
    // Command: monthly report
    // -------------------------------------------------------------------------

    #[Test]
    public function generate_monthly_report_command_creates_db_row_fr_19_1(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 06:00:00')); // 1st of month

        $this->artisan(GenerateSummaryReportCommand::class, ['--type' => 'monthly'])
            ->assertSuccessful();

        $this->assertDatabaseCount('summary_reports', 1);

        $report = SummaryReport::first();
        $this->assertSame('monthly', $report->type);
        $this->assertArrayHasKey('payments_succeeded_amount', $report->payload);
    }

    // -------------------------------------------------------------------------
    // Command: invalid type fails gracefully
    // -------------------------------------------------------------------------

    #[Test]
    public function generate_report_command_rejects_invalid_type(): void
    {
        $this->artisan(GenerateSummaryReportCommand::class, ['--type' => 'daily'])
            ->assertFailed();

        $this->assertDatabaseCount('summary_reports', 0);
    }

    // -------------------------------------------------------------------------
    // Admin list page
    // -------------------------------------------------------------------------

    #[Test]
    public function admin_can_view_summary_reports_list_fr_19_1(): void
    {
        SummaryReport::create([
            'type'         => 'weekly',
            'period_from'  => '2026-03-30',
            'period_to'    => '2026-04-05',
            'payload'      => ['payments_total_count' => 5, 'payments_succeeded_amount' => 200.0, 'payments_failed_amount' => 0, 'ledger_entries_count' => 3, 'ledger_in_amount' => 200.0, 'ledger_out_amount' => 150.0],
            'generated_at' => now(),
        ]);

        app()->setLocale('en');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.summary-reports.index'));

        $response->assertOk();
        $response->assertViewIs('admin.finances.summary-reports.index');
        $response->assertSee('Weekly & Monthly Summary Reports');
        $response->assertSee('Download as Excel');
        $response->assertSee('2026-03-30');
    }

    // -------------------------------------------------------------------------
    // Admin download Excel (XLSX)
    // -------------------------------------------------------------------------

    #[Test]
    public function admin_can_download_summary_report_excel_fr_19_1(): void
    {
        if (! extension_loaded('zip')) {
            $this->markTestSkipped('PHP ext-zip is required to generate XLSX (PhpSpreadsheet).');
        }
        if (! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $this->markTestSkipped('phpoffice/phpspreadsheet is not available (autoload / install).');
        }

        $report = SummaryReport::create([
            'type'         => 'monthly',
            'period_from'  => '2026-03-01',
            'period_to'    => '2026-03-31',
            'payload'      => [
                'payments_total_count'       => 10,
                'payments_succeeded_amount'  => 500.0,
                'payments_failed_amount'     => 50.0,
                'ledger_entries_count'       => 8,
                'ledger_in_amount'           => 500.0,
                'ledger_out_amount'          => 300.0,
                'payments_by_status'         => [
                    ['status' => 'SUCCEEDED', 'cnt' => 10, 'total' => 500.0],
                ],
            ],
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.summary-reports.download', $report->id));

        $response->assertOk();
        // XLSX content-type
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        // Filename contains the report type and period dates
        $contentDisposition = $response->headers->get('Content-Disposition', '');
        $this->assertStringContainsString('nubl-report-monthly', $contentDisposition);
        $this->assertStringContainsString('2026-03-01', $contentDisposition);
        $this->assertStringContainsString('2026-03-31', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
        // Response body is non-empty (binary XLSX data)
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
        // XLSX files start with PK (ZIP magic bytes)
        $this->assertStringStartsWith('PK', $content);
    }

    // -------------------------------------------------------------------------
    // Non-admin is forbidden
    // -------------------------------------------------------------------------

    #[Test]
    public function non_admin_cannot_access_summary_reports(): void
    {
        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $recipient->assignRole('recipient');

        $this->actingAs($recipient)
            ->get(route('admin.finances.summary-reports.index'))
            ->assertForbidden();
    }
}
