<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\WeeklyAllowanceNotification;
use App\Services\Recipient\AllowanceService;
use App\Support\WeeklyAllowanceSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllowanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'allowances.configure', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('allowances.configure');
    }

    #[Test]
    public function admin_can_view_allowance_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.settings.allowances.edit'));

        $response->assertOk();
        $response->assertSee(__('Weekly allowance'), false);
    }

    #[Test]
    public function guest_cannot_view_allowance_settings(): void
    {
        $this->get(route('admin.settings.allowances.edit'))->assertRedirect();
    }

    #[Test]
    public function admin_without_allowance_permission_cannot_update_allowance_settings(): void
    {
        $admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('admin.settings.allowances.update'), [
                'weekly_allowance_sar' => 300,
            ])
            ->assertForbidden();

        $this->assertNull(SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE));
    }

    #[Test]
    public function admin_can_schedule_weekly_allowance_change(): void
    {
        Notification::fake();

        config(['app.timezone' => 'Asia/Riyadh']);
        Carbon::setTestNow(Carbon::parse('2026-04-03 12:00:00', 'Asia/Riyadh'));

        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)
            ->put(route('admin.settings.allowances.update'), [
                'weekly_allowance_sar' => 300,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('300.00', SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE));
        $storedAt = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT);
        $this->assertNotNull($storedAt);
        $expected = WeeklyAllowanceSettings::nextEffectiveBoundary();
        $this->assertSame($expected->toIso8601String(), Carbon::parse($storedAt)->toIso8601String());

        $activity = Activity::query()
            ->where('description', 'allowance_settings.weekly_limit_scheduled')
            ->latest('id')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('schedule_pending_weekly_allowance', $activity->properties->get('decision'));
        $this->assertEqualsWithDelta(300.0, (float) $activity->properties->get('weekly_allowance_sar'), 0.001);
        $this->assertSame($storedAt, $activity->properties->get('effective_at'));

        Notification::assertSentTo(
            $recipient,
            WeeklyAllowanceNotification::class,
            fn (WeeklyAllowanceNotification $n) => $n->event === 'scheduled'
                && abs($n->limitSar - 300.0) < 0.001
                && $n->effectiveAtIso !== null
                && $n->actor?->is($this->admin)
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function weekly_limit_stays_config_until_effective_date(): void
    {
        Notification::fake();

        config(['app.timezone' => 'UTC']);
        config(['recipient.weekly_allowance_limit' => 400]);

        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', 'UTC'));

        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE, '300.00');
        SystemSetting::setValue(
            WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT,
            Carbon::parse('2026-04-12 00:00:00', 'UTC')->toIso8601String()
        );

        $this->assertSame(400.0, AllowanceService::weeklyLimit());

        Carbon::setTestNow(Carbon::parse('2026-04-12 00:00:01', 'UTC'));
        $this->assertSame(300.0, AllowanceService::weeklyLimit());
        $this->assertSame('300.00', SystemSetting::getValue(WeeklyAllowanceSettings::KEY_ACTIVE));
        $this->assertNull(SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE));

        Notification::assertSentTo(
            $recipient,
            WeeklyAllowanceNotification::class,
            fn (WeeklyAllowanceNotification $n) => $n->event === 'applied' && abs($n->limitSar - 300.0) < 0.001 && $n->actor === null
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function validation_rejects_out_of_range_allowance(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.settings.allowances.update'), [
                'weekly_allowance_sar' => 0,
            ]);

        $response->assertSessionHasErrors('weekly_allowance_sar');
    }
}
