<?php

namespace App\Http\Services;

use App\Permissions\PermissionDefinitions;
use App\Rbac\ProtectedRoles;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementService
{
    public function __construct(
        private PermissionRegistrar $permissionRegistrar,
        private AuditService $auditService
    ) {}

    /**
     * Localized display label; falls back to the technical name if no translation exists.
     */
    public function labelForPermission(string $name): string
    {
        $key = 'rbac.permission.'.$name;
        $trans = __($key);

        return $trans !== $key ? $trans : $name;
    }

    public function rolesForIndex(): Collection
    {
        return Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{key: string, label: string, permissions: list<array{permission: Permission, label: string}>}>
     */
    public function permissionGroupsForForm(): array
    {
        $groups = [];
        foreach (PermissionDefinitions::uiGroups() as $g) {
            $items = [];
            foreach (Permission::query()
                ->whereIn('name', $g['names'])
                ->orderBy('name')
                ->get() as $permission) {
                $items[] = [
                    'permission' => $permission,
                    'label' => $this->labelForPermission($permission->name),
                ];
            }
            $groups[] = [
                'key' => $g['key'],
                'label' => __($g['label_key']),
                'permissions' => $items,
            ];
        }

        $defined = collect(PermissionDefinitions::all());
        $others = Permission::query()
            ->whereNotIn('name', $defined->all())
            ->orderBy('name')
            ->get();

        if ($others->isNotEmpty()) {
            $items = [];
            foreach ($others as $permission) {
                $items[] = [
                    'permission' => $permission,
                    'label' => $this->labelForPermission($permission->name),
                ];
            }
            $groups[] = [
                'key' => 'other',
                'label' => __('rbac.group.other_permissions'),
                'permissions' => $items,
            ];
        }

        return $groups;
    }

    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => config('auth.defaults.guard', 'web'),
        ]);
        $role->syncPermissions($data['permissions'] ?? []);
        $this->permissionRegistrar->forgetCachedPermissions();

        $permissionNames = collect($data['permissions'] ?? [])->sort()->values()->all();
        $this->auditService->log('role', 'created', [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $permissionNames,
            'permission_count' => count($permissionNames),
        ]);

        return $role;
    }

    /**
     * @throws InvalidArgumentException When the admin role would be left without permissions.
     */
    public function updateRole(Role $role, array $data): void
    {
        $permissions = $data['permissions'] ?? [];

        if ($role->name === 'admin' && $permissions === []) {
            throw new InvalidArgumentException(__('rbac.admin_must_keep_permissions'));
        }

        $roleId = $role->id;
        $previousName = $role->name;
        $permissionsBefore = $role->permissions()->pluck('name')->sort()->values()->all();

        if (! ProtectedRoles::isProtected($role->name) && isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        $role->syncPermissions($permissions);
        $this->permissionRegistrar->forgetCachedPermissions();

        $role->refresh();
        $role->load('permissions');
        $permissionsAfter = $role->permissions->pluck('name')->sort()->values()->all();

        $this->auditService->log('role', 'updated', [
            'role_id' => $roleId,
            'role_name' => $role->name,
            'previous_name' => $previousName !== $role->name ? $previousName : null,
            'permissions_before' => $permissionsBefore,
            'permissions_after' => $permissionsAfter,
            'permissions_changed' => $permissionsBefore !== $permissionsAfter,
        ]);
    }

    /**
     * @return string|null Error message, or null when deletion succeeded.
     */
    public function deleteRole(Role $role): ?string
    {
        if (ProtectedRoles::isProtected($role->name)) {
            return __('rbac.cannot_delete_protected');
        }

        if ($role->users()->exists()) {
            return __('rbac.cannot_delete_role_in_use');
        }

        $roleId = $role->id;
        $roleName = $role->name;
        $permissionNames = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->delete();
        $this->permissionRegistrar->forgetCachedPermissions();

        $this->auditService->log('role', 'deleted', [
            'role_id' => $roleId,
            'role_name' => $roleName,
            'permissions' => $permissionNames,
            'permission_count' => count($permissionNames),
        ]);

        return null;
    }
}
