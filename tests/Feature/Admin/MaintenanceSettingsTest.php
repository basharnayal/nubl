<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\MaintenanceSettingsController;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (file_exists(storage_path('framework/maintenance.php'))) {
            Artisan::call('up');
        }
    }

    protected function tearDown(): void
    {
        if (file_exists(storage_path('framework/maintenance.php'))) {
            @Artisan::call('up');
        }
        parent::tearDown();
    }

    #[Test]
    public function admin_can_view_maintenance_settings_page(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.settings.maintenance.edit'));

        $response->assertOk();
        $response->assertSee(__('Maintenance mode'), false);
    }

    #[Test]
    public function admin_can_enable_and_disable_maintenance_mode(): void
    {
        // Allow POST /disable while the app is in maintenance (tests have no bypass cookie).
        $this->withoutMiddleware(PreventRequestsDuringMaintenance::class);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $admin->assignRole('admin');

        $this->assertFalse(file_exists(storage_path('framework/maintenance.php')));

        $enable = $this->actingAs($admin)->post(route('admin.settings.maintenance.enable'));
        $enable->assertOk();
        $enable->assertSee(__('Maintenance mode enabled'), false);

        $this->assertTrue(file_exists(storage_path('framework/maintenance.php')));
        $secret = SystemSetting::getValue(MaintenanceSettingsController::SETTING_BYPASS_SECRET);
        $this->assertNotEmpty($secret);

        $bypassUrl = rtrim(config('app.url'), '/').'/'.$secret;
        $enable->assertSee($bypassUrl, false);

        $disable = $this->actingAs($admin)->post(route('admin.settings.maintenance.disable'));
        $disable->assertRedirect(route('admin.settings.maintenance.edit'));
        $disable->assertSessionHas('success');

        $this->assertFalse(file_exists(storage_path('framework/maintenance.php')));
        $this->assertNull(SystemSetting::getValue(MaintenanceSettingsController::SETTING_BYPASS_SECRET));
    }

    #[Test]
    public function enabling_maintenance_twice_returns_warning_redirect(): void
    {
        $this->withoutMiddleware(PreventRequestsDuringMaintenance::class);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.settings.maintenance.enable'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.settings.maintenance.enable'))
            ->assertRedirect(route('admin.settings.maintenance.edit'))
            ->assertSessionHas('warning');
    }

    #[Test]
    public function disable_returns_info_when_maintenance_is_already_off(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $admin->assignRole('admin');

        $this->assertFalse(file_exists(storage_path('framework/maintenance.php')));

        $this->actingAs($admin)
            ->post(route('admin.settings.maintenance.disable'))
            ->assertRedirect(route('admin.settings.maintenance.edit'))
            ->assertSessionHas('info');
    }

    #[Test]
    public function edit_page_shows_bypass_url_when_maintenance_is_active_and_secret_exists(): void
    {
        $this->withoutMiddleware(PreventRequestsDuringMaintenance::class);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.settings.maintenance.enable'))->assertOk();

        $response = $this->actingAs($admin)->get(route('admin.settings.maintenance.edit'));

        $response->assertOk();
        $response->assertViewHas('maintenanceActive', true);
        $response->assertViewHas('bypassUrl', function (?string $url): bool {
            return is_string($url) && $url !== '' && str_starts_with($url, rtrim(config('app.url'), '/').'/');
        });
    }

    #[Test]
    public function guest_cannot_access_maintenance_settings(): void
    {
        $this->get(route('admin.settings.maintenance.edit'))->assertRedirect(route('login'));
    }
}
