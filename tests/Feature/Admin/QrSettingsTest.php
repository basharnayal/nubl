<?php

namespace Tests\Feature\Admin;

use App\Models\Request as RequestModel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\RedemptionService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QrSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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
    public function admin_can_view_qr_settings_fr_8_3(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.qr.edit'));

        $response->assertOk();
        $response->assertViewIs('admin.settings.qr');
    }

    #[Test]
    public function admin_can_update_qr_ttl_and_redemption_uses_it_fr_8_3(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.settings.qr.update'), [
                'ttl_minutes' => 240,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('240', SystemSetting::getValue('qr.ttl_minutes'));

        $activity = Activity::query()
            ->where('description', 'qr_settings.updated')
            ->latest('id')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('save_ttl', $activity->properties->get('decision'));
        $this->assertSame(240, $activity->properties->get('ttl_minutes'));

        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $provider->assignRole('provider');
        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $recipient->assignRole('recipient');

        $req = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 10,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $redemption = RedemptionService::generateForRequest($req);
        $this->assertNotNull($redemption);
        $this->assertSame(240, $redemption->ttl_minutes);
        $this->assertEqualsWithDelta(
            now()->addMinutes(240)->getTimestamp(),
            $redemption->redeem_expires_at->getTimestamp(),
            3
        );
    }

    #[Test]
    public function admin_without_qr_permission_cannot_update_qr_settings(): void
    {
        Role::findByName('admin')->revokePermissionTo('qr.configure_ttl');

        $this->actingAs($this->admin)
            ->put(route('admin.settings.qr.update'), ['ttl_minutes' => 240])
            ->assertForbidden();

        $this->assertNull(SystemSetting::getValue('qr.ttl_minutes'));
    }

    #[Test]
    public function non_admin_cannot_update_qr_settings(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $user->assignRole('recipient');

        $this->actingAs($user)
            ->put(route('admin.settings.qr.update'), ['ttl_minutes' => 240])
            ->assertForbidden();
    }
}
