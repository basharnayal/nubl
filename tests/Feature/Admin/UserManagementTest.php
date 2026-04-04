<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'donor', 'recipient', 'provider'] as $name) {
            Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->createAdmin();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.manage.users.index'));

        $response->assertOk();
        $response->assertViewIs('admin.manage.users.index');
        $response->assertViewHas('users');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = $this->createAdmin();
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        $response = $this->actingAs($admin)->get(route('admin.manage.users.index', ['role' => 'donor']));

        $response->assertOk();
    }

    public function test_admin_can_filter_users_by_status(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.manage.users.index', ['status' => 'active']));

        $response->assertOk();
    }

    public function test_admin_can_view_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.manage.users.show', $user));

        $response->assertOk();
        $response->assertViewIs('admin.manage.users.show');
        $response->assertViewHas('user', $user);
    }

    public function test_admin_can_create_donor(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.manage.users.store'), [
            'name' => 'New Donor',
            'email' => 'donor@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'membership_type' => 'donor',
            'phone_number' => '966501234567',
            'roles' => ['donor'],
        ]);

        $response->assertRedirect(route('admin.manage.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'donor@example.com',
            'name' => 'New Donor',
            'membership_type' => 'donor',
            'is_active' => true,
        ]);

        $created = User::where('email', 'donor@example.com')->first();
        $this->assertNotNull($created);
        $activity = Activity::where('causer_id', $admin->id)
            ->where('description', 'user.created')
            ->where('properties->user_id', $created->id)
            ->first();
        $this->assertNotNull($activity);
        $this->assertNotNull($activity->created_at);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        $user->assignRole('donor');

        $response = $this->actingAs($admin)->put(route('admin.manage.users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'membership_type' => 'donor',
            'phone_number' => '966509876543',
            'password' => '',
            'roles' => ['donor'],
        ]);

        $response->assertRedirect(route('admin.manage.users.show', $user));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('966509876543', $user->phone_number);

        $activity = Activity::where('causer_id', $admin->id)
            ->where('description', 'user.updated')
            ->where('properties->user_id', $user->id)
            ->first();
        $this->assertNotNull($activity);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.manage.users.destroy', $user));

        $response->assertRedirect(route('admin.manage.users.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $activity = Activity::where('causer_id', $admin->id)
            ->where('description', 'user.deleted')
            ->where('properties->deleted_user_id', $user->id)
            ->first();
        $this->assertNotNull($activity);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->delete(route('admin.manage.users.destroy', $admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.manage.users.deactivate', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->is_active);

        $activity = Activity::where('causer_id', $admin->id)
            ->where('description', 'user.deactivated')
            ->where('properties->user_id', $user->id)
            ->first();
        $this->assertNotNull($activity);
    }

    public function test_admin_can_reactivate_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($admin)->post(route('admin.manage.users.reactivate', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->is_active);

        $activity = Activity::where('causer_id', $admin->id)
            ->where('description', 'user.reactivated')
            ->where('properties->user_id', $user->id)
            ->first();
        $this->assertNotNull($activity);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.manage.users.deactivate', $admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $admin->refresh();
        $this->assertTrue($admin->is_active);
    }

    public function test_admin_cannot_deactivate_last_admin(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $otherAdmin->update(['is_active' => false]);

        $response = $this->actingAs($admin)->post(route('admin.manage.users.deactivate', $otherAdmin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        $response = $this->actingAs($donor)->get(route('admin.manage.users.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get(route('admin.manage.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_deactivated_user_is_logged_out_when_accessing_protected_route(): void
    {
        $user = User::factory()->inactive()->create();
        $user->assignRole('donor');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
