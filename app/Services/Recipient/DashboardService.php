<?php

namespace App\Services\Recipient;

use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get all data required for the recipient dashboard.
     */
    public function getDashboardData(User $user): array
    {
        $remainingLimit = AllowanceService::getRemainingLimit($user->id);
        $weeklyLimit = AllowanceService::weeklyLimit();

        $activeStatuses = ['REQUESTED', 'APPROVED', 'REDEEMABLE']; 
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

        $dashboardMyRequests = RequestModel::forRecipient($user->id)
            ->with(['provider.providerProfile', 'items.menuItem'])
            ->whereIn('status', [
                'REQUESTED',
                'ADMIN_PENDING',
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

        return [
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
        ];
    }

    /**
     * Build chart data for Activity Overview.
     */
    public function activityChartData(int $recipientId, ?Carbon $selectedDate = null): array
    {
        $hasSelectedDate = $selectedDate !== null;
        $selectedDate = $selectedDate ?? Carbon::now();
        $startDate = $hasSelectedDate
            ? (clone $selectedDate)->startOfWeek(Carbon::SUNDAY)
            : (clone $selectedDate)->subDays(6)->startOfDay();
        $endDate = $hasSelectedDate
            ? (clone $selectedDate)->endOfWeek(Carbon::SATURDAY)
            : (clone $selectedDate)->endOfDay();

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

        return [
            'categories' => $categories,
            'series' => $series,
            'selectedIndex' => $selectedIndex,
            'rangeText' => $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M, Y'),
        ];
    }
}
