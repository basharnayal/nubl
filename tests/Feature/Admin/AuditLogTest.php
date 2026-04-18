<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'donor', 'recipient', 'provider'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_access_audit_logs_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.audit-logs.index');
    }

    public function test_audit_logs_page_passes_expected_view_data(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));

        $response->assertViewHas('logs');
        $response->assertViewHas('totalCount');
        $response->assertViewHas('todayCount');
        $response->assertViewHas('financeCount');
        $response->assertViewHas('authCount');
        $response->assertViewHas('registrationCount');
    }

    public function test_page_displays_existing_audit_records(): void
    {
        $admin = $this->createAdmin();
        activity()->log('auth.login');

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('auth.login');
    }

    public function test_non_admin_cannot_access_audit_logs(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        $response = $this->actingAs($donor)->get(route('admin.audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_audit_logs(): void
    {
        $response = $this->get(route('admin.audit-logs.index'));

        $response->assertRedirect();
    }

    public function test_search_filter_narrows_results(): void
    {
        $admin = $this->createAdmin();
        activity()->log('auth.login');
        activity()->log('finance.payments_exported');

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['search' => 'finance']));

        $response->assertOk();
        $response->assertSee('finance.payments_exported');
    }

    public function test_causer_id_filter_works(): void
    {
        $admin = $this->createAdmin();
        activity()->causedBy($admin)->log('user.updated');

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['causer_id' => $admin->id]));

        $response->assertOk();
    }

    public function test_date_range_filter_works(): void
    {
        $admin = $this->createAdmin();
        activity()->log('auth.login');

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', [
            'date_from' => today()->toDateString(),
            'date_to'   => today()->toDateString(),
        ]));

        $response->assertOk();
    }
}
