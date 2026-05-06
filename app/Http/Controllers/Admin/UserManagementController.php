<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin full CRUD for users + deactivate/reactivate (FR-12.1, FR-20.1).
 * Thin controller: delegates to UserService.
 */
class UserManagementController extends Controller
{
    private const SORTABLE = ['name', 'email', 'status', 'created_at'];

    public function __construct(
        private UserService $userService,
    ) {}

    public function index(Request $request): View
    {
        $users = $this->buildUserIndexQuery($request, 15);

        return view('admin.manage.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load([
            'roles',
            'recipientProfile',
            'recipientKycDetails',
            'providerProfile',
            'providerOperatingInfo',
            'providerFinancialInfo',
            'providerDocuments',
        ]);

        return view('admin.manage.users.show', compact('user'));
    }

    public function create(): View
    {
        return view('admin.manage.users.create', [
            'allRoles' => $this->rolesForUserForms(),
            'businessCategories' => config('provider.business_categories'),
            'serviceTypes' => config('provider.service_types'),
            'weekdays' => config('provider.weekdays'),
            'nationalities' => config('nationalities', []),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $this->userService->createUser($request->validated(), $request);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.manage.users.index')->with('success', __('User created successfully.'));
    }

    public function edit(User $user): View
    {
        $user->load([
            'roles',
            'recipientProfile',
            'recipientKycDetails',
            'providerProfile',
            'providerOperatingInfo',
            'providerFinancialInfo',
            'providerDocuments',
        ]);

        return view('admin.manage.users.edit', [
            'user' => $user,
            'allRoles' => $this->rolesForUserForms(),
            'businessCategories' => config('provider.business_categories'),
            'serviceTypes' => config('provider.service_types'),
            'weekdays' => config('provider.weekdays'),
            'weekdayKeys' => array_keys(config('provider.weekdays')),
            'nationalities' => config('nationalities', []),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    private function rolesForUserForms(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->orderBy('name')
            ->get();
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->updateUser($user, $request->validated(), $request);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.manage.users.show', $user)->with('success', __('User updated successfully.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->userService->deleteUser($user, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.manage.users.index')->with('success', __('User deleted successfully.'));
    }

    public function deactivate(User $user): RedirectResponse
    {
        try {
            $this->userService->deactivate($user, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('User deactivated successfully.'));
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->userService->reactivate($user, auth()->user());

        return back()->with('success', __('User reactivated successfully.'));
    }

    private function buildUserIndexQuery(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = User::with(['roles', 'providerProfile', 'recipientProfile']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $sort = in_array($request->sort, self::SORTABLE) ? $request->sort : null;
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        if ($sort) {
            $query->orderBy($sort, $direction);
        } else {
            // Priority: pending_approval → active → login-disabled → rejected
            $query->orderByRaw("
                CASE
                    WHEN status = 'pending_approval'              THEN 1
                    WHEN status = 'active'  AND is_active = 1    THEN 2
                    WHEN is_active = 0      AND status != 'rejected' THEN 3
                    WHEN status = 'rejected'                      THEN 4
                    ELSE 5
                END
            ")
                ->orderByRaw(
                    '(SELECT COUNT(*) FROM model_has_roles mhr
                  INNER JOIN roles r ON r.id = mhr.role_id
                  WHERE mhr.model_id = users.id AND mhr.model_type = ? AND r.name = ?) DESC',
                    [User::class, 'admin']
                )
                ->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
