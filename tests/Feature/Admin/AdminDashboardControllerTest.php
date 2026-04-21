<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function admin_dashboard_route_renders_overview_from_service(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $overview = [
            'system_status' => [[
                'key' => 'gateway',
                'label_key' => 'dashboard.status.gateway',
                'icon' => 'fa-solid fa-credit-card',
                'is_ok' => true,
                'tooltip_key' => 'dashboard.status.tooltip.gateway_on',
                'tooltip_params' => [],
                'route' => null,
                'severity' => 'ok',
            ]],
            'attention_items' => [[
                'severity' => 'medium',
                'icon' => 'fa-solid fa-inbox',
                'labelKey' => 'dashboard.attention.new_requests.label',
                'count' => 1,
                'descKey' => 'dashboard.attention.new_requests.desc',
                'descParams' => [],
                'actionKey' => null,
                'actionRoute' => null,
            ]],
            'kpis' => [[
                'key' => 'pending_approvals',
                'label_key' => 'dashboard.kpi.pending_approvals.label',
                'value' => 1,
                'value_format' => 'integer',
                'sub_key' => 'dashboard.kpi.pending_approvals.sub',
                'sub_params' => ['recipients' => 1, 'providers' => 0],
                'icon' => 'fa-solid fa-user-clock',
                'color' => 'amber',
                'route' => null,
                'action_key' => 'dashboard.kpi.pending_approvals.action',
            ]],
            'financial' => [
                'system_wallet_balance' => 0.0,
                'successful_payments_count' => 0,
                'successful_payments_amount' => 0.0,
                'pending_count' => 0,
                'pending_amount' => 0.0,
                'failed_count' => 0,
                'failed_amount' => 0.0,
                'fund_inbound_system' => 0.0,
                'fund_outbound_system' => 0.0,
                'transfers_to_providers' => 0.0,
            ],
            'platform' => [
                'total_users' => 1,
                'donors' => 1,
                'recipients' => 0,
                'providers' => 0,
                'approved_providers' => 0,
                'pending_users' => 0,
                'requests_30d' => 0,
                'fulfilled_30d' => 0,
            ],
            'recent_activity' => [[
                'id' => 1,
                'description' => 'maintenance.enabled',
                'log_name' => 'audit',
                'causer_name' => 'System',
                'created_at' => now(),
            ]],
        ];

        $mock = Mockery::mock(AdminDashboardService::class);
        $mock->shouldReceive('getOverview')->once()->andReturn($overview);
        $this->app->instance(AdminDashboardService::class, $mock);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('overview', fn (array $data): bool => array_key_exists('system_status', $data)
            && array_key_exists('attention_items', $data)
            && array_key_exists('kpis', $data));
    }
}
