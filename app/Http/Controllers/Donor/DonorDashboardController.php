<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Support\PseudonymousRequestId;
use App\Support\RequestTypeLabel;
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

        // ── Donor payment aggregates (3 queries → 1 + pluck) ────────────────
        $donorPaymentBase = Payment::where('sponsor_id', $sponsorId)
            ->where('status', Payment::STATUS_SUCCEEDED);

        $donorAgg = (clone $donorPaymentBase)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total, MAX(created_at) as last_at')
            ->first();

        $donorTotalDonated = (float) $donorAgg->total;
        $donorDonationCount = (int) $donorAgg->cnt;
        $donorLastContribution = $donorAgg->last_at ? Carbon::parse($donorAgg->last_at) : null;
        $donorLastContributionHuman = $donorLastContribution
            ? ($donorLastContribution->isPast() ? $donorLastContribution->diffForHumans() : $donorLastContribution->translatedFormat('M d, Y'))
            : null;

        $donorPaymentIds = (clone $donorPaymentBase)->pluck('id');

        // ── Request payment link aggregates (3 queries → 1 + pluck) ─────────
        $linkAgg = RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->selectRaw('COUNT(DISTINCT request_id) as funded_count, COALESCE(SUM(amount), 0) as total_allocated')
            ->first();

        $donorRequestsFunded = (int) $linkAgg->funded_count;
        $donorAmountAllocated = (float) $linkAgg->total_allocated;

        $donorRequestIds = RequestPaymentLink::whereIn('payment_id', $donorPaymentIds)
            ->distinct()->pluck('request_id')->filter();

        // Delivered = request is redeemable or fully fulfilled (funded and in recipient's hands).
        $donorRequestsDelivered = $donorRequestIds->isNotEmpty()
            ? (int) RequestModel::whereIn('id', $donorRequestIds)
                ->whereIn('status', ['REDEEMABLE', 'FULFILLED'])
                ->count()
            : 0;

        $donorImpactTimeline = $this->donorImpactTimeline($donorPaymentIds);
        $donorChartData = $this->donorImpactChartData($donorPaymentIds);

        // ── Platform need aggregates (4 queries → 2) ────────────────────────
        $platformAgg = RequestModel::whereIn('status', ['REQUESTED', 'APPROVED', 'REDEEMABLE'])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(reserved_amount), 0) as total, COUNT(DISTINCT recipient_id) as waiting')
            ->first();

        $pendingRequestsCount = (int) $platformAgg->cnt;
        $pendingAmount = (float) $platformAgg->total;
        $recipientsWaiting = (int) $platformAgg->waiting;
        $fulfilledCount = (int) RequestModel::where('status', 'FULFILLED')->count();
        $fundedPercent = $fulfilledCount > 0
            ? min(100, (int) (($fulfilledCount / max(1, $fulfilledCount + $pendingRequestsCount)) * 100))
            : 0;

        $donorTransparency = [
            'requests_from_your_funds' => $donorRequestsFunded,  // Unique requests funded by donor
            'requests_delivered' => $donorRequestsDelivered,     // REDEEMABLE or FULFILLED — funded and in recipient's hands
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

            $type = RequestTypeLabel::forRequest($request);

            return [
                'pseudonymous_id' => PseudonymousRequestId::make($request->id),
                'date' => $displayAt->translatedFormat('M d, Y'),
                'time' => $displayAt->translatedFormat('H:i'),
                'amount' => (float) $link->amount,
                'type' => $type,
                'status' => $this->mapRequestStatusForDonor($request),
                'status_key' => $request->status,
            ];
        })->filter()->sortByDesc(fn ($r) => $r['date'].' '.$r['time'])->values()->take(15)->toArray();
    }

    private function mapRequestStatusForDonor(RequestModel $request): string
    {
        if ($request->status === 'REDEEMABLE' && $request->redemption?->status === 'REDEEMED') {
            return __('Code scanned at provider');
        }

        return match ($request->status) {
            'FULFILLED' => __('Delivered'),
            'REDEEMABLE' => __('Ready for redemption'),
            'APPROVED' => __('Request approved'),
            'REQUESTED' => __('Allocated'),
            default => __('Allocated'),
        };
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
            $date = Carbon::parse($monthKey.'-01');
            $categories[] = $date->translatedFormat('M Y');
            $series[] = (float) $items->sum('amount');
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }
}
