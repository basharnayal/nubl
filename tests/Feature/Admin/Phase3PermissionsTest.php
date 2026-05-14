<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase3PermissionsTest extends TestCase
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

    // =========================================================================
    // finance.manage
    // =========================================================================

    #[Test]
    public function admin_with_finance_manage_can_access_finances_overview(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.finances.overview'))
            ->assertOk();
    }

    #[Test]
    public function admin_with_finance_manage_can_access_payments_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.index'))
            ->assertOk();
    }

    #[Test]
    public function admin_with_finance_manage_can_access_fund_transactions_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.index'))
            ->assertOk();
    }

    #[Test]
    public function admin_without_finance_manage_gets_403_on_overview(): void
    {
        $this->revoke('finance.manage');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.overview'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_finance_manage_gets_403_on_payments_index(): void
    {
        $this->revoke('finance.manage');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_finance_manage_gets_403_on_fund_transactions_index(): void
    {
        $this->revoke('finance.manage');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.index'))
            ->assertForbidden();
    }

    #[Test]
    public function non_admin_donor_cannot_access_finance_routes(): void
    {
        $donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $donor->assignRole('donor');

        $this->actingAs($donor)->get(route('admin.finances.overview'))->assertForbidden();
        $this->actingAs($donor)->get(route('admin.finances.payments.index'))->assertForbidden();
        $this->actingAs($donor)->get(route('admin.finances.fund-transactions.index'))->assertForbidden();
    }

    // =========================================================================
    // reports.export_csv — finance context
    // =========================================================================

    #[Test]
    public function admin_with_finance_manage_but_without_export_csv_gets_403_on_payments_export(): void
    {
        $this->revoke('reports.export_csv');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.export'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_with_finance_manage_but_without_export_csv_gets_403_on_fund_transactions_export(): void
    {
        $this->revoke('reports.export_csv');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.export'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_with_finance_manage_and_export_csv_can_export_payments(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function admin_with_finance_manage_and_export_csv_can_export_fund_transactions(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // =========================================================================
    // reports.export_pdf — finance context
    // =========================================================================

    #[Test]
    public function admin_with_finance_manage_but_without_export_pdf_gets_403_on_payments_export_pdf(): void
    {
        $this->revoke('reports.export_pdf');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.export-pdf'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_with_finance_manage_but_without_export_pdf_gets_403_on_fund_transactions_export_pdf(): void
    {
        $this->revoke('reports.export_pdf');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.export-pdf'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_finance_manage_gets_403_on_payments_export_even_with_export_csv(): void
    {
        $this->revoke('finance.manage');

        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.export'))
            ->assertForbidden();
    }

    // =========================================================================
    // maintenance.manage
    // =========================================================================

    #[Test]
    public function admin_with_maintenance_manage_can_access_maintenance_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.maintenance.edit'))
            ->assertOk();
    }

    #[Test]
    public function admin_without_maintenance_manage_gets_403_on_maintenance_settings(): void
    {
        $this->revoke('maintenance.manage');

        $this->actingAs($this->admin)
            ->get(route('admin.settings.maintenance.edit'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_maintenance_manage_gets_403_on_maintenance_enable(): void
    {
        $this->revoke('maintenance.manage');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.maintenance.enable'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_maintenance_manage_gets_403_on_maintenance_disable(): void
    {
        $this->revoke('maintenance.manage');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.maintenance.disable'))
            ->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_access_maintenance_settings(): void
    {
        $donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $donor->assignRole('donor');

        $this->actingAs($donor)
            ->get(route('admin.settings.maintenance.edit'))
            ->assertForbidden();
    }

    // =========================================================================
    // audit_logs.view
    // =========================================================================

    #[Test]
    public function admin_with_audit_logs_view_can_access_audit_logs_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk();
    }

    #[Test]
    public function admin_without_audit_logs_view_gets_403_on_audit_logs_index(): void
    {
        $this->revoke('audit_logs.view');

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_access_audit_logs(): void
    {
        $donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $donor->assignRole('donor');

        $this->actingAs($donor)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // reports.export_csv — audit logs context
    // =========================================================================

    #[Test]
    public function admin_with_audit_logs_view_but_without_export_csv_gets_403_on_audit_logs_export(): void
    {
        $this->revoke('reports.export_csv');

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_with_audit_logs_view_and_export_csv_can_export_audit_logs(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // =========================================================================
    // reports.export_pdf — audit logs context
    // =========================================================================

    #[Test]
    public function admin_with_audit_logs_view_but_without_export_pdf_gets_403_on_audit_logs_export_pdf(): void
    {
        $this->revoke('reports.export_pdf');

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export-pdf'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_audit_logs_view_gets_403_on_export_even_with_export_csv(): void
    {
        $this->revoke('audit_logs.view');

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.export'))
            ->assertForbidden();
    }

    private function revoke(string $permission): void
    {
        Role::findByName('admin')->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
