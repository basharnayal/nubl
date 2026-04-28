<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\RoleManagementService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function roles_for_index_returns_roles_sorted_with_permissions_count(): void
    {
        Role::create(['name' => 'ops_manager']);

        $roles = app(RoleManagementService::class)->rolesForIndex();

        $names = $roles->pluck('name')->all();
        $sorted = $names;
        sort($sorted);

        $this->assertSame($sorted, $names);
        $this->assertNotNull($roles->firstWhere('name', 'admin'));
        $this->assertTrue($roles->firstWhere('name', 'admin')->permissions_count > 0);
    }

    #[Test]
    public function permission_groups_for_form_includes_other_group_and_label_for_permission_fallback(): void
    {
        $service = app(RoleManagementService::class);

        Permission::create(['name' => 'custom.unmapped.permission']);

        $groups = $service->permissionGroupsForForm();
        $other = collect($groups)->firstWhere('key', 'other');

        $this->assertNotNull($other);
        $this->assertTrue(collect($other['permissions'])->pluck('permission.name')->contains('custom.unmapped.permission'));

        app()->setLocale('ar');
        $this->assertNotSame('accounts.approve', $service->labelForPermission('accounts.approve'));
        $this->assertSame('non.existing.permission', $service->labelForPermission('non.existing.permission'));
    }

    #[Test]
    public function create_role_then_delete_role_in_use_and_after_release(): void
    {
        $service = app(RoleManagementService::class);
        $permission = Permission::query()->firstOrFail()->name;

        $role = $service->createRole([
            'name' => 'qa_role',
            'permissions' => [$permission],
        ]);

        $this->assertDatabaseHas('roles', ['name' => 'qa_role']);
        $this->assertTrue($role->hasPermissionTo($permission));

        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $user->assignRole('qa_role');

        $inUseError = $service->deleteRole($role->fresh());
        $this->assertSame(__('rbac.cannot_delete_role_in_use'), $inUseError);
        $this->assertDatabaseHas('roles', ['name' => 'qa_role']);

        $user->removeRole('qa_role');
        $deleted = $service->deleteRole($role->fresh());

        $this->assertNull($deleted);
        $this->assertDatabaseMissing('roles', ['name' => 'qa_role']);
    }
}
