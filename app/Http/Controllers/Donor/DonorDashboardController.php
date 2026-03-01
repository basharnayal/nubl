<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use Carbon\Carbon;

class DonorDashboardController extends Controller
{
    /**
     * Display the donor dashboard with aggregated stats.
     * FR-4.1: Aggregated statistics (no PII).
     * FR-4.2: No PII in dashboards.
     */
    public function index()
    {
        $sponsorId = auth()->id();

        $donorTotalDonated = (float) Payment::where('sponsor_id', $sponsorId)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->sum('amount');

        $donorPaymentIds = Payment::where('sponsor_id', $sponsorId)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->pluck('id');

        $donorRequestsFunded = RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->distinct('request_id')
            ->count('request_id');

        $donorRequestIds = RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->pluck('request_id')
            ->unique();

        $donorBeneficiariesHelped = RequestModel::whereIn('id', $donorRequestIds)
            ->distinct('recipient_id')
            ->count('recipient_id');

        $donorLastContribution = Payment::where('sponsor_id', $sponsorId)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->latest('created_at')
            ->first()?->created_at;

        $donorImpactTimeline = $this->donorImpactTimeline($donorPaymentIds);
        $donorChartData = $this->donorImpactChartData($donorPaymentIds);

        // Platform need (real-time, encouraging)
        $pendingRequestsCount = RequestModel::whereIn('status', ['REQUESTED', 'APPROVED', 'REDEEMABLE'])
            ->count();
        $pendingAmount = RequestModel::whereIn('status', ['REQUESTED', 'APPROVED', 'REDEEMABLE'])
            ->sum('reserved_amount');
        $recipientsWaiting = RequestModel::whereIn('status', ['REQUESTED', 'APPROVED', 'REDEEMABLE'])
            ->selectRaw('COUNT(DISTINCT recipient_id) as total')->value('total') ?? 0;
        $fulfilledCount = RequestModel::where('status', 'FULFILLED')->count();
        $fundedPercent = $fulfilledCount > 0
            ? min(100, (int) (($fulfilledCount / max(1, $fulfilledCount + $pendingRequestsCount)) * 100))
            : 0;

        $donorTransparency = [
            'requests_from_your_funds' => $donorRequestsFunded,
            'meals_items_delivered' => 0,
            'meals_percent' => 65,
            'baskets_percent' => 35,
        ];

        return view('donor.dashboard', compact(
            'donorTotalDonated',
            'donorRequestsFunded',
            'donorBeneficiariesHelped',
            'donorLastContribution',
            'donorImpactTimeline',
            'donorChartData',
            'pendingRequestsCount',
            'pendingAmount',
            'recipientsWaiting',
            'fundedPercent',
            'donorTransparency'
        ));
    }

    private function donorImpactTimeline($donorPaymentIds): array
    {
        if ($donorPaymentIds->isEmpty()) {
            return [];
        }

        return RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->with('request')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($link) => [
                'request_id' => $link->request_id,
                'amount' => $link->amount,
                'created_at' => $link->created_at,
            ])
            ->toArray();
    }

    private function donorImpactChartData($donorPaymentIds): array
    {
        $categories = [];
        $series = [];

        for ($i = 23; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $categories[] = $date->translatedFormat('M Y');

            if ($donorPaymentIds->isEmpty()) {
                $series[] = 0;
            } else {
                $monthTotal = Payment::whereIn('id', $donorPaymentIds)
                    ->where('status', Payment::STATUS_SUCCEEDED)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount');
                $series[] = (float) $monthTotal;
            }
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }
}
