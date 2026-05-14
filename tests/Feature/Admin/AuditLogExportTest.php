<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class AuditLogExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function export_csv_streams_content_with_correct_headers(): void
    {
        activity()
            ->causedBy($this->admin)
            ->withProperties(['entity' => 'auth', 'action' => 'login', 'ip' => '127.0.0.1', 'user_agent' => 'TestAgent'])
            ->log('auth.login');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('id', $csv);
        $this->assertStringContainsString('log_name', $csv);
        $this->assertStringContainsString('description', $csv);
        $this->assertStringContainsString('auth.login', $csv);
        $this->assertStringContainsString('127.0.0.1', $csv);
        $this->assertStringContainsString('TestAgent', $csv);
    }

    #[Test]
    public function export_csv_respects_search_filter(): void
    {
        activity()->withProperties(['entity' => 'auth'])->log('auth.login');
        activity()->withProperties(['entity' => 'finance'])->log('finance.payments_exported');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export', ['search' => 'finance']));

        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('finance.payments_exported', $csv);
        $this->assertStringNotContainsString('auth.login', $csv);
    }

    #[Test]
    public function export_csv_respects_entity_filter(): void
    {
        activity()->withProperties(['entity' => 'auth'])->log('auth.login');
        activity()->withProperties(['entity' => 'finance'])->log('finance.payment_created');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export', ['entity' => 'auth']));

        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('auth.login', $csv);
        $this->assertStringNotContainsString('finance.payment_created', $csv);
    }

    #[Test]
    public function export_csv_respects_date_range_filter(): void
    {
        $old = Activity::forceCreate([
            'log_name' => 'default',
            'description' => 'old.entry',
            'properties' => ['entity' => 'auth'],
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        activity()->withProperties(['entity' => 'auth'])->log('today.entry');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export', [
                'date_from' => today()->toDateString(),
                'date_to' => today()->toDateString(),
            ]));

        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('today.entry', $csv);
        $this->assertStringNotContainsString('old.entry', $csv);
    }

    #[Test]
    public function export_csv_writes_audit_trail_entry(): void
    {
        activity()->log('auth.login');

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export', ['search' => 'auth']));

        $activity = Activity::query()
            ->where('description', 'audit.audit_logs_exported')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('audit_logs_csv', $activity->properties->get('export_type'));
        $this->assertSame('auth', $activity->properties->get('filters')['search'] ?? null);
    }

    #[Test]
    public function export_pdf_downloads_with_correct_headers(): void
    {
        activity()
            ->causedBy($this->admin)
            ->withProperties(['entity' => 'finance', 'ip' => '10.0.0.1'])
            ->log('finance.payment_created');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export-pdf'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function export_pdf_includes_sha256_checksum_header(): void
    {
        activity()->withProperties(['entity' => 'auth'])->log('auth.login');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export-pdf'));

        $response->assertOk();
        $response->assertHeader('X-Content-SHA256');

        $hash = $response->headers->get('X-Content-SHA256');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);

        $computedHash = hash('sha256', $response->getContent());
        $this->assertSame($hash, $computedHash, 'X-Content-SHA256 header must match SHA-256 of response body.');
    }

    #[Test]
    public function export_pdf_writes_audit_trail_entry(): void
    {
        activity()->log('auth.login');

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export-pdf', ['entity' => 'auth']));

        $activity = Activity::query()
            ->where('description', 'audit.audit_logs_exported')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('audit_logs_pdf', $activity->properties->get('export_type'));
        $this->assertSame('auth', $activity->properties->get('filters')['entity'] ?? null);
    }

    #[Test]
    public function export_pdf_respects_filters(): void
    {
        activity()->withProperties(['entity' => 'auth'])->log('auth.login');
        activity()->withProperties(['entity' => 'finance'])->log('finance.payment_created');

        $renderedView = view('admin.audit-logs.exports.audit-logs-pdf', [
            'logs' => Activity::where('properties->entity', 'auth')->get(),
            'generated_at' => now(),
        ])->render();

        $this->assertStringContainsString('auth.login', $renderedView);
        $this->assertStringNotContainsString('finance.payment_created', $renderedView);
    }

    #[Test]
    public function non_admin_cannot_export_csv(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        $response = $this->actingAs($donor)
            ->get(route('admin.audit-logs.export'));

        $response->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_export_pdf(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        $response = $this->actingAs($donor)
            ->get(route('admin.audit-logs.export-pdf'));

        $response->assertForbidden();
    }

    #[Test]
    public function guest_is_redirected_from_export_routes(): void
    {
        $this->get(route('admin.audit-logs.export'))->assertRedirect();
        $this->get(route('admin.audit-logs.export-pdf'))->assertRedirect();
    }
}
