<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Queries\Admin\UserIndexQuery;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin full CRUD for users + deactivate/reactivate (FR-12.1, FR-20.1).
 * Thin controller: delegates to UserService and UserIndexQuery.
 */
class UserManagementController extends Controller
{
    public function __construct(
        private UserService $userService,
        private UserIndexQuery $userIndexQuery
    ) {}

    public function index(Request $request): View
    {
        $users = ($this->userIndexQuery)($request, 15);

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
            'recipientProfile',
            'recipientKycDetails',
            'providerProfile',
            'providerOperatingInfo',
            'providerFinancialInfo',
        ]);

        return view('admin.manage.users.edit', [
            'user' => $user,
            'businessCategories' => config('provider.business_categories'),
            'serviceTypes' => config('provider.service_types'),
            'weekdays' => config('provider.weekdays'),
            'nationalities' => config('nationalities', []),
        ]);
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
}
