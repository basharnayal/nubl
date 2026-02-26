<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
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
        // Donor-specific impact (placeholder until donations table exists)
        $donorTotalDonated = 0;
        $donorRequestsFunded = 0;
        $donorBeneficiariesHelped = 0;
        $donorLastContribution = null;
        $donorImpactTimeline = [];
        $donorChartData = $this->donorImpactChartPlaceholder();

        // Platform need (real-time, encouraging)
        $pendingRequestsCount = RequestModel::whereIn('status', ['PENDING', 'PROVIDER_APPROVED', 'ADMIN_PENDING'])
            ->count();
        $pendingAmount = RequestModel::whereIn('status', ['PENDING', 'PROVIDER_APPROVED', 'ADMIN_PENDING'])
            ->sum('reserved_amount');
        $recipientsWaiting = RequestModel::whereIn('status', ['PENDING', 'PROVIDER_APPROVED', 'ADMIN_PENDING'])
            ->selectRaw('COUNT(DISTINCT recipient_id) as total')->value('total') ?? 0;
        $fulfilledCount = RequestModel::where('status', 'FULFILLED')->count();
        $fundedPercent = $fulfilledCount > 0
            ? min(100, (int) (($fulfilledCount / max(1, $fulfilledCount + $pendingRequestsCount)) * 100))
            : 0;

        // Transparency: how donor's amounts helped (placeholder - donor-specific when donations linked)
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

    /**
     * Placeholder chart data for last 24 months (donor impact).
     */
    private function donorImpactChartPlaceholder(): array
    {
        $categories = [];
        $series = [];
        for ($i = 23; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $categories[] = $date->translatedFormat('M Y');
            $series[] = 0; // Replace with real donor data when donations linked
        }
        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }
}
