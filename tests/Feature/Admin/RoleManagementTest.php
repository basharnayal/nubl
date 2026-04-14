<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\RoleManagementService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
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
    public function service_refuses_to_delete_protected_role(): void
    {
        $role = Role::findByName('provider');

        $error = app(RoleManagementService::class)->deleteRole($role);

        $this->assertSame(__('rbac.cannot_delete_protected'), $error);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'provider',
        ]);
        $this->assertFalse(
            Activity::query()
                ->where('description', 'role.deleted')
                ->get()
                ->contains(fn (Activity $activity) => $activity->properties->get('role_id') === $role->id)
        );
    }

    #[Test]
    public function controller_refuses_to_delete_protected_role(): void
    {
        $role = Role::findByName('donor');

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', __('rbac.cannot_delete_protected'));

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'donor',
        ]);
    }

    #[Test]
    public function service_does_not_rename_protected_role_but_can_update_permissions(): void
    {
        $role = Role::findByName('admin');

        app(RoleManagementService::class)->updateRole($role, [
            'name' => 'super_admin',
            'permissions' => ['roles.manage'],
        ]);

        $role->refresh();

        $this->assertSame('admin', $role->name);
        $this->assertTrue($role->hasPermissionTo('roles.manage'));
        $this->assertSame(['roles.manage'], $role->permissions()->pluck('name')->all());

        $activity = Activity::query()
            ->where('description', 'role.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($role->id, $activity->properties->get('role_id'));
        $this->assertSame('admin', $activity->properties->get('role_name'));
        $this->assertNull($activity->properties->get('previous_name'));
        $this->assertSame(['roles.manage'], $activity->properties->get('permissions_after'));
    }

    #[Test]
    public function service_rejects_empty_admin_permissions_without_changing_role(): void
    {
        $role = Role::findByName('admin');
        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        try {
            app(RoleManagementService::class)->updateRole($role, ['permissions' => []]);
            $this->fail('Expected the admin role to keep at least one permission.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(__('rbac.admin_must_keep_permissions'), $e->getMessage());
        }

        $this->assertSame($before, $role->fresh()->permissions()->pluck('name')->sort()->values()->all());
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'role.updated',
        ]);
    }

    #[Test]
    public function controller_rejects_empty_admin_permissions_without_changing_role(): void
    {
        $role = Role::findByName('admin');
        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        $this->actingAs($this->admin)
            ->from(route('admin.roles.edit', $role))
            ->put(route('admin.roles.update', $role), [
                'permissions' => [],
            ])
            ->assertRedirect(route('admin.roles.edit', $role))
            ->assertSessionHas('error', __('rbac.admin_must_keep_permissions'));

        $this->assertSame($before, $role->fresh()->permissions()->pluck('name')->sort()->values()->all());
    }
}
