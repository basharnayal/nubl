<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\OrderRedemption;
use App\Models\Request as RequestModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProviderDashboardController extends Controller
{
    public function __invoke(): View
    {
        $providerId = auth()->id();

        $since30 = now()->subDays(30);

        $aggregate = $this->requestAggregates($providerId, $since30);

        $pendingRequestsCount = (int) ($aggregate->pending_requests ?? 0);
        $inPipelineCount = (int) ($aggregate->in_pipeline ?? 0);
        $fulfilledLast30Count = (int) ($aggregate->fulfilled_30 ?? 0);
        $valueFulfilledLast30 = (float) ($aggregate->value_30 ?? 0);

        $pendingProofCount = RequestModel::forProvider($providerId)
            ->whereHas('redemption', function ($query) {
                $query->where('status', 'REDEEMED')
                    ->whereDoesntHave('proof');
            })
            ->count();

        $qrRedeemedLast30Count = OrderRedemption::query()
            ->where('provider_id', $providerId)
            ->where('status', 'REDEEMED')
            ->where('updated_at', '>=', $since30)
            ->count();

        $weeklyFulfilledChart = $this->weeklyFulfilledChartData($providerId);

        $recentRequests = RequestModel::forProvider($providerId)
            ->with(['recipient', 'items.menuItem'])
            ->latest()
            ->take(5)
            ->get();

        $statusLabels = [
            'REQUESTED' => __('Requested'),
            'APPROVED' => __('Approved'),
            'ADMIN_PENDING' => __('Admin Pending'),
            'ADMIN_APPROVED' => __('Admin Approved'),
            'REDEEMABLE' => __('Redeemable'),
            'FULFILLED' => __('Fulfilled'),
            'REJECTED' => __('Rejected by provider'),
            'CANCELLED' => __('Cancelled'),
            'ADMIN_REJECTED' => __('Rejected by admin'),
        ];

        return view('provider.dashboard', compact(
            'pendingRequestsCount',
            'pendingProofCount',
            'inPipelineCount',
            'fulfilledLast30Count',
            'valueFulfilledLast30',
            'qrRedeemedLast30Count',
            'weeklyFulfilledChart',
            'recentRequests',
            'statusLabels'
        ));
    }

    private function requestAggregates(int $providerId, Carbon $since30): object
    {
        $sinceStr = $since30->format('Y-m-d H:i:s');

        $row = DB::table('requests')
            ->where('provider_id', $providerId)
            ->selectRaw(
                "SUM(CASE WHEN status = 'REQUESTED' THEN 1 ELSE 0 END) as pending_requests, ".
                "SUM(CASE WHEN status IN ('APPROVED','ADMIN_PENDING','ADMIN_APPROVED','REDEEMABLE') THEN 1 ELSE 0 END) as in_pipeline, ".
                'SUM(CASE WHEN status = \'FULFILLED\' AND updated_at >= ? THEN 1 ELSE 0 END) as fulfilled_30, '.
                'COALESCE(SUM(CASE WHEN status = \'FULFILLED\' AND updated_at >= ? THEN reserved_amount ELSE 0 END), 0) as value_30',
                [$sinceStr, $sinceStr]
            )
            ->first();

        return $row ?? (object) [
            'pending_requests' => 0,
            'in_pipeline' => 0,
            'fulfilled_30' => 0,
            'value_30' => 0,
        ];
    }

    /**
     * Last 8 weeks: fulfilled count per week (one query).
     *
     * @return array{categories: list<string>, series: list<int>}
     */
    private function weeklyFulfilledChartData(int $providerId): array
    {
        $categories = [];
        $weekRanges = [];

        for ($i = 7; $i >= 0; $i--) {
            $anchor = Carbon::now()->subWeeks($i);
            $start = $anchor->copy()->startOfWeek();
            $end = $anchor->copy()->endOfWeek();
            $categories[] = $start->translatedFormat('M j');
            $weekRanges[] = [$start, $end];
        }

        $bindings = [];
        $selectParts = [];
        foreach ($weekRanges as $idx => [$ws, $we]) {
            $selectParts[] = 'SUM(CASE WHEN updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as w'.$idx;
            $bindings[] = $ws->format('Y-m-d H:i:s');
            $bindings[] = $we->format('Y-m-d H:i:s');
        }

        $sql = 'SELECT '.implode(', ', $selectParts).' FROM `requests` WHERE `provider_id` = ? AND `status` = ?';
        $bindings[] = $providerId;
        $bindings[] = 'FULFILLED';

        $row = DB::selectOne($sql, $bindings);

        $series = [];
        for ($i = 0; $i < 8; $i++) {
            $series[] = (int) ($row ? ($row->{'w'.$i} ?? 0) : 0);
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }
}
