<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AllocationEngineServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\PendingAllocation;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR-24.1: Admin pause/resume for the allocation engine (global + per-provider).
 * Thin controller — all logic lives in AllocationEngineService.
 */
class AllocationController extends Controller
{
    public function __construct(
        private readonly AllocationEngineServiceInterface $engineService
    ) {}

    public function status(): View
    {
        $globallyPaused = SystemSetting::getValue('allocation_engine.paused') === '1';
        $providers = User::role('provider')
            ->select(['id', 'name', 'allocation_paused'])
            ->orderBy('name')
            ->get();
        $pendingCount = PendingAllocation::count();

        return view('admin.allocation.status', compact('globallyPaused', 'providers', 'pendingCount'));
    }

    public function pauseGlobal(Request $request): RedirectResponse
    {
        $this->engineService->pauseGlobal($request->user());

        return back()->with('success', __('Allocation engine paused globally.'));
    }

    public function resumeGlobal(Request $request): RedirectResponse
    {
        $this->engineService->resumeGlobal($request->user());

        return back()->with('success', __('Allocation engine resumed. Pending allocations will be processed shortly.'));
    }

    public function pauseProvider(Request $request, User $provider): RedirectResponse
    {
        $this->engineService->pauseProvider($provider, $request->user());

        return back()->with('success', __('Allocation paused for provider :name.', ['name' => $provider->name]));
    }

    public function resumeProvider(Request $request, User $provider): RedirectResponse
    {
        $this->engineService->resumeProvider($provider, $request->user());

        return back()->with('success', __('Allocation resumed for provider :name. Pending allocations will be processed shortly.', ['name' => $provider->name]));
    }
}
