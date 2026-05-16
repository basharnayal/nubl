<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\RecipientAllowanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipientController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $remainingLimit = RecipientAllowanceService::getRemainingLimit($user->id);
        $weeklyLimit = RecipientAllowanceService::weeklyLimit();

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

        // Dashboard "My Requests": keep only statuses the recipient still cares about (pending + redeemable groups).
        $dashboardMyRequests = RequestModel::forRecipient($user->id)
            ->with(['provider.providerProfile', 'items.menuItem'])
            ->whereIn('status', [
                // pending group
                'REQUESTED',
                'ADMIN_PENDING',
                // redeemable group
                'APPROVED',
                'ADMIN_APPROVED',
                'REDEEMABLE',
            ])
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

        $activityChartData = $this->activityChartData($user->id);

        $latestProvider = User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->has('providerProfile')
            ->with('providerProfile')
            ->latest()
            ->first();

        $communityFulfilledThisWeek = RequestModel::query()
            ->where('status', 'FULFILLED')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->count();

        return view('recipient.dashboard', [
            'remainingLimit' => $remainingLimit,
            'weeklyLimit' => $weeklyLimit,
            'activeRequestsCount' => $activeRequestsCount,
            'pendingCount' => $pendingCount,
            'completedOrdersCount' => $completedOrdersCount,
            'providersCount' => $providersCount,
            'recentRequests' => $recentRequests,
            'dashboardMyRequests' => $dashboardMyRequests,
            'providers' => $providers,
            'activityChartData' => $activityChartData,
            'latestProvider' => $latestProvider,
            'communityFulfilledThisWeek' => $communityFulfilledThisWeek,
        ]);
    }

    public function chartDataApi(Request $request): \Illuminate\Http\JsonResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();
        $recipientId = $request->user()->id;

        $data = $this->activityChartData($recipientId, $date);

        return response()->json($data);
    }

    /**
     * Build chart data for Activity Overview: amount spent (fulfilled) per day for the week containing the given date.
     */
    private function activityChartData(int $recipientId, ?Carbon $selectedDate = null): array
    {
        $selectedDate = $selectedDate ?? Carbon::now();
        // Fixed week: Sunday to Saturday
        $startDate = (clone $selectedDate)->startOfWeek(Carbon::SUNDAY);
        $endDate = (clone $selectedDate)->endOfWeek(Carbon::SATURDAY);

        $dayExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        $daily = RequestModel::forRecipient($recipientId)
            ->where('status', 'FULFILLED')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->selectRaw("{$dayExpression} as day, COALESCE(SUM(reserved_amount), 0) as total")
            ->groupByRaw($dayExpression)
            ->pluck('total', 'day');

        $categories = [];
        $series = [];
        $selectedIndex = -1;

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $startDate)->addDays($i);
            $categories[] = $date->translatedFormat('D');
            $series[] = (float) ($daily[$date->format('Y-m-d')] ?? 0);
            
            if ($date->isSameDay($selectedDate)) {
                $selectedIndex = $i;
            }
        }

        // Handle RTL manually by reversing data for Arabic
        if (app()->getLocale() === 'ar') {
            $categories = array_reverse($categories);
            $series = array_reverse($series);
            $selectedIndex = 6 - $selectedIndex;
        }

        return [
            'categories' => $categories,
            'series' => $series,
            'selectedIndex' => $selectedIndex,
            'rangeText' => $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M, Y'),
        ];
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
