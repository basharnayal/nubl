<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Http\Services\RecipientAllowanceService;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipientController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $remainingLimit = RecipientAllowanceService::getRemainingLimit($user->id);
        $weeklyLimit = RecipientAllowanceService::WEEKLY_LIMIT;

        $activeStatuses = ['REQUESTED', 'APPROVED', 'REDEEMABLE']; // APPROVED = provider adopted, REDEEMABLE = accepted with City Fund
        $pendingStatuses = ['REQUESTED', 'APPROVED'];

        $activeRequestsCount = RequestModel::forRecipient($user->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        $pendingCount = RequestModel::forRecipient($user->id)
            ->whereIn('status', $pendingStatuses)
            ->count();

        $completedOrdersCount = RequestModel::forRecipient($user->id)
            ->where('status', 'FULFILLED')
            ->count();

        $providersCount = User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->has('providerProfile')
            ->count();

        $recentRequests = RequestModel::forRecipient($user->id)
            ->with(['provider.providerProfile', 'items.menuItem'])
            ->latest()
            ->take(5)
            ->get();

        $providers = User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->has('providerProfile')
            ->with('providerProfile')
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('recipient.dashboard', [
            'remainingLimit' => $remainingLimit,
            'weeklyLimit' => $weeklyLimit,
            'activeRequestsCount' => $activeRequestsCount,
            'pendingCount' => $pendingCount,
            'completedOrdersCount' => $completedOrdersCount,
            'providersCount' => $providersCount,
            'recentRequests' => $recentRequests,
            'providers' => $providers,
        ]);
    }

    public function providersList(): View
    {
        $providers = User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->with(['providerProfile', 'providerOperatingInfo'])
            ->has('providerProfile')
            ->orderBy('name')
            ->get();

        return view('recipient.providers-list', ['providers' => $providers]);
    }

    /**
     * Show menu items for a provider (for modal or standalone).
     */
    public function providerMenu(Request $request, User $provider): View
    {
        if ($provider->membership_type !== User::MEMBERSHIP_PROVIDER || ! $provider->providerProfile) {
            abort(404);
        }

        $menuItems = $provider->providerMenuItems()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $providerName = $provider->providerProfile->full_name_en ?? $provider->providerProfile->full_name_ar ?? $provider->name;

        return view('recipient.provider-menu', [
            'provider' => $provider,
            'providerName' => $providerName,
            'menuItems' => $menuItems,
        ]);
    }
}
