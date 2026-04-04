<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Services\RoleManagementService;
use App\Rbac\ProtectedRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private RoleManagementService $roleManagementService
    ) {}

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => $this->roleManagementService->rolesForIndex(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'groups' => $this->roleManagementService->permissionGroupsForForm(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleManagementService->createRole($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', __('rbac.role_created'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'groups' => $this->roleManagementService->permissionGroupsForForm(),
            'selected' => $role->permissions->pluck('name')->all(),
            'isProtected' => ProtectedRoles::isProtected($role->name),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $this->roleManagementService->updateRole($role, $request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')
            ->with('success', __('rbac.role_updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $error = $this->roleManagementService->deleteRole($role);

        if ($error !== null) {
            return redirect()->route('admin.roles.index')->with('error', $error);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', __('rbac.role_deleted'));
    }
}
