<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerCoverageTest extends TestCase
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
}

