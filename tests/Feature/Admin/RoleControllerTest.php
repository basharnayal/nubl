<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\ProtectedRoles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleControllerTest extends TestCase
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
    public function index_and_create_pages_render_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.roles.create'))
            ->assertOk();
    }

    #[Test]
    public function admin_can_store_edit_update_and_destroy_non_protected_role(): void
    {
        $permissionA = Permission::query()->firstOrFail()->name;
        $permissionB = Permission::query()->skip(1)->firstOrFail()->name;

        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'qa_role',
                'permissions' => [$permissionA],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success', __('rbac.role_created'));

        $role = Role::findByName('qa_role');

        $this->actingAs($this->admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk();

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'qa_role_updated',
                'permissions' => [$permissionB],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success', __('rbac.role_updated'));

        $updated = Role::findByName('qa_role_updated');
        $this->assertTrue($updated->hasPermissionTo($permissionB));

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $updated))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success', __('rbac.role_deleted'));

        $this->assertDatabaseMissing('roles', ['name' => 'qa_role_updated']);
    }

    #[Test]
    public function destroy_returns_error_when_role_is_in_use(): void
    {
        $role = Role::create(['name' => 'ops_role']);
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $user->assignRole($role->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', __('rbac.cannot_delete_role_in_use'));

        $this->assertDatabaseHas('roles', ['name' => 'ops_role']);
    }

    #[Test]
    public function admin_with_roles_manage_permission_can_access_roles_pages(): void
    {
        // RoleSeeder grants the admin role every permission, including roles.manage.
        $this->assertTrue($this->admin->can('roles.manage'));

        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.roles.create'))
            ->assertOk();
    }

    #[Test]
    public function admin_without_roles_manage_permission_is_forbidden_from_roles_pages(): void
    {
        $this->revokeRolesManageFromAdminRole();

        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.roles.create'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_without_roles_manage_permission_cannot_mutate_roles_via_direct_requests(): void
    {
        $existing = Role::create(['name' => 'qa_role']);
        $permission = Permission::query()->firstOrFail()->name;

        $this->revokeRolesManageFromAdminRole();

        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'forbidden_role',
                'permissions' => [$permission],
            ])
            ->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'forbidden_role']);

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', $existing), [
                'name' => 'qa_role_renamed',
                'permissions' => [$permission],
            ])
            ->assertForbidden();
        $this->assertDatabaseHas('roles', ['name' => 'qa_role']);
        $this->assertDatabaseMissing('roles', ['name' => 'qa_role_renamed']);

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $existing))
            ->assertForbidden();
        $this->assertDatabaseHas('roles', ['name' => 'qa_role']);
    }

    #[Test]
    public function sidebar_roles_permissions_link_visibility_follows_roles_manage_permission(): void
    {
        $label = __('rbac.nav.roles_permissions');

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($label);

        $this->revokeRolesManageFromAdminRole();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee($label);
    }

    #[Test]
    public function protected_roles_cannot_be_deleted_or_renamed_via_http(): void
    {
        foreach (ProtectedRoles::NAMES as $name) {
            $role = Role::findByName($name);

            // Rename attempt is silently ignored — the protected role keeps its name.
            $this->actingAs($this->admin)
                ->put(route('admin.roles.update', $role), [
                    'name' => $name.'_renamed',
                    'permissions' => $role->permissions()->pluck('name')->all(),
                ]);
            $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $name]);
            $this->assertDatabaseMissing('roles', ['name' => $name.'_renamed']);

            // Delete attempt is refused.
            $this->actingAs($this->admin)
                ->delete(route('admin.roles.destroy', $role))
                ->assertSessionHas('error', __('rbac.cannot_delete_protected'));
            $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $name]);
        }
    }

    private function revokeRolesManageFromAdminRole(): void
    {
        Role::findByName('admin')->revokePermissionTo('roles.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
