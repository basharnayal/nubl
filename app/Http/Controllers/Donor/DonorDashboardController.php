<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $donorDonationCount = Payment::where('sponsor_id', $sponsorId)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->count();

        $donorRequestsFunded = (int) DB::table('request_payment_links')
            ->whereIn('payment_id', $donorPaymentIds)
            ->selectRaw('COUNT(DISTINCT request_id) as cnt')
            ->value('cnt');

        $donorRequestIds = RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->pluck('request_id')
            ->unique()
            ->filter();

        // Requests funded by donor that reached REDEEMABLE (code scanned) or FULFILLED (delivered)
        $donorRequestsDelivered = $donorRequestIds->isNotEmpty()
            ? (int) RequestModel::whereIn('id', $donorRequestIds)
                ->whereIn('status', ['REDEEMABLE', 'FULFILLED'])
                ->count()
            : 0;

        $donorAmountAllocated = (float) RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->sum('amount');

        $donorLastContribution = Payment::where('sponsor_id', $sponsorId)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->latest('created_at')
            ->first()?->created_at;
        $donorLastContributionHuman = $donorLastContribution
            ? ($donorLastContribution->isPast() ? $donorLastContribution->diffForHumans() : $donorLastContribution->translatedFormat('M d, Y'))
            : null;

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
            'requests_from_your_funds' => $donorRequestsFunded,  // Unique requests funded by donor
            'requests_delivered' => $donorRequestsDelivered,     // Those with REDEEMABLE or FULFILLED
            'amount_allocated' => $donorAmountAllocated,          // Sum from request_payment_links
        ];

        return view('donor.dashboard', compact(
            'donorTotalDonated',
            'donorDonationCount',
            'donorRequestsFunded',
            'donorRequestsDelivered',
            'donorAmountAllocated',
            'donorLastContribution',
            'donorLastContributionHuman',
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
     * Fulfilled and ongoing requests supported by the donor.
     * FR-25.2: Pseudonymous IDs.
     * No PII — only dates, amounts, type, status.
     */
    private function donorImpactTimeline($donorPaymentIds): array
    {
        if ($donorPaymentIds->isEmpty()) {
            return [];
        }

        $links = RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->with([
                'request:id,status',
                'request.items.menuItem.menuItemCategory:id,name',
                'request.redemption.proof:id,order_redemption_id,fulfilled_at',
            ])
            ->latest()
            ->limit(25)
            ->get();

        return $links->map(function ($link) {
            $request = $link->request;
            if (! $request) {
                return null;
            }

            $proof = $request->redemption?->proof;
            $fulfilledAt = $proof?->fulfilled_at;
            $displayAt = $fulfilledAt ?? $link->created_at;

            $type = $this->resolveRequestTypeLabel($request);

            return [
                'pseudonymous_id' => $this->pseudonymousRequestId($request->id),
                'date' => $displayAt->translatedFormat('M d, Y'),
                'time' => $displayAt->translatedFormat('H:i'),
                'amount' => (float) $link->amount,
                'type' => $type,
                'status' => $this->mapRequestStatusForDonor($request->status),
                'status_key' => $request->status,
            ];
        })->filter()->sortByDesc(fn ($r) => $r['date'] . ' ' . $r['time'])->values()->take(15)->toArray();
    }

    /** FR-25.2: Pseudonymous ID instead of real request ID. */
    private function pseudonymousRequestId(int $requestId): string
    {
        return 'R-' . strtoupper(substr(hash('sha256', 'req_' . $requestId . config('app.key')), 0, 8));
    }

    private function mapRequestStatusForDonor(?string $status): string
    {
        return match ($status) {
            'FULFILLED' => __('Delivered'),
            'REDEEMABLE' => __('Code scanned at provider'),
            'APPROVED' => __('Request approved'),
            'REQUESTED' => __('Allocated'),
            default => __('Allocated'),
        };
    }

    /**
     * Resolve human-readable type label. Avoids showing IDs (e.g. "213") or invalid data.
     */
    private function resolveRequestTypeLabel(RequestModel $request): string
    {
        $firstItem = $request->items->first();
        if (! $firstItem?->menuItem) {
            return __('Request');
        }

        $menuItem = $firstItem->menuItem;

        // Prefer category name (e.g. "Bread", "Rice Dishes") — must be non-numeric
        $categoryName = $menuItem->menuItemCategory?->name;
        if ($categoryName && ! preg_match('/^\d+$/', (string) $categoryName)) {
            return $categoryName;
        }

        // Fallback: menu item name (e.g. "Family meal package") — more descriptive
        $itemName = $menuItem->name;
        if ($itemName && ! preg_match('/^\d+$/', (string) $itemName)) {
            return $itemName;
        }

        // Legacy: map category slug to human-readable
        $legacyCategory = $menuItem->category;
        if ($legacyCategory && ! preg_match('/^\d+$/', (string) $legacyCategory)) {
            return match (strtolower((string) $legacyCategory)) {
                'meal', 'meals' => __('Meals'),
                'bakery' => __('Bakery'),
                'basket' => __('Food basket'),
                'catering' => __('Catering'),
                'grocery' => __('Grocery'),
                default => ucfirst((string) $legacyCategory),
            };
        }

        return __('Request');
    }

    /**
     * Chart data: only periods (months) where the donor had donations.
     * Sparse display — no empty months. Chronological order.
     */
    private function donorImpactChartData($donorPaymentIds): array
    {
        if ($donorPaymentIds->isEmpty()) {
            return ['categories' => [], 'series' => []];
        }

        $payments = Payment::whereIn('id', $donorPaymentIds)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->get(['created_at', 'amount']);

        $grouped = $payments->groupBy(fn ($p) => $p->created_at->format('Y-m'));

        $categories = [];
        $series = [];
        foreach ($grouped->sortKeys() as $monthKey => $items) {
            $date = Carbon::parse($monthKey . '-01');
            $categories[] = $date->translatedFormat('M Y');
            $series[] = (float) $items->sum('amount');
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }
}
