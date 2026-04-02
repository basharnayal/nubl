<?php

namespace App\Http\Services\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregates for admin financial monitoring.
 * Source of truth: payments.status and ewallets.balance (via FundTransaction ledger).
 */
class AdminFinancialService
{
    /**
     * @return array<string, float|int>
     */
    public function getOverview(): array
    {
        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->first();
        $systemWalletId = $systemWallet?->id;

        $successfulCount = Payment::query()->where('status', Payment::STATUS_SUCCEEDED)->count();
        $successfulAmount = (float) Payment::query()->where('status', Payment::STATUS_SUCCEEDED)->sum('amount');

        $pendingStatuses = [
            Payment::STATUS_INITIATED,
            Payment::STATUS_PENDING,
            Payment::STATUS_PROCESSING,
        ];
        $pendingBase = Payment::query()->whereIn('status', $pendingStatuses);
        $failedBase = Payment::query()->where('status', Payment::STATUS_FAILED);

        $fundInbound = $systemWalletId
            ? (float) FundTransaction::query()
                ->where('wallet_id', $systemWalletId)
                ->where('direction', FundTransaction::DIRECTION_IN)
                ->sum('amount')
            : 0.0;

        $fundOutbound = $systemWalletId
            ? (float) FundTransaction::query()
                ->where('wallet_id', $systemWalletId)
                ->where('direction', FundTransaction::DIRECTION_OUT)
                ->sum('amount')
            : 0.0;

        $transfersToProviders = $systemWalletId
            ? (float) FundTransaction::query()
                ->where('wallet_id', $systemWalletId)
                ->where('direction', FundTransaction::DIRECTION_OUT)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->sum('amount')
            : 0.0;

        return [
            'system_wallet_balance' => $systemWallet ? (float) $systemWallet->balance : 0.0,
            'successful_payments_count' => $successfulCount,
            'successful_payments_amount' => $successfulAmount,
            'pending_count' => (int) (clone $pendingBase)->count(),
            'pending_amount' => (float) (clone $pendingBase)->sum('amount'),
            'failed_count' => (int) (clone $failedBase)->count(),
            'failed_amount' => (float) (clone $failedBase)->sum('amount'),
            'fund_inbound_system' => $fundInbound,
            'fund_outbound_system' => $fundOutbound,
            'transfers_to_providers' => $transfersToProviders,
        ];
    }

    /**
     * Full enriched summary for a date range.
     * Used by FR-19.1 scheduled reports and the on-demand report view.
     *
     * @return array<string, mixed>
     */
    public function getRangeSummary(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        // ── Payments (gateway) ────────────────────────────────────────────────
        $paymentRows = Payment::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(amount),0) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $paymentsTotal   = (int)   Payment::query()->whereBetween('created_at', [$from, $to])->count();
        $paymentsSuccAmt = (float) Payment::query()->whereBetween('created_at', [$from, $to])->where('status', Payment::STATUS_SUCCEEDED)->sum('amount');
        $paymentsSuccCnt = (int)   Payment::query()->whereBetween('created_at', [$from, $to])->where('status', Payment::STATUS_SUCCEEDED)->count();
        $paymentsFailAmt = (float) Payment::query()->whereBetween('created_at', [$from, $to])->where('status', Payment::STATUS_FAILED)->sum('amount');
        $paymentsFailCnt = (int)   Payment::query()->whereBetween('created_at', [$from, $to])->where('status', Payment::STATUS_FAILED)->count();
        $paymentsPendCnt = (int)   Payment::query()->whereBetween('created_at', [$from, $to])
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])->count();

        // ── Fund ledger ───────────────────────────────────────────────────────
        $systemWallet   = Ewallet::where('owner_type', 'SYSTEM')->first();
        $systemWalletId = $systemWallet?->id;

        $ledgerIn  = (float) FundTransaction::query()->whereBetween('created_at', [$from, $to])->where('direction', FundTransaction::DIRECTION_IN)->sum('amount');
        $ledgerOut = (float) FundTransaction::query()->whereBetween('created_at', [$from, $to])->where('direction', FundTransaction::DIRECTION_OUT)->sum('amount');
        $ledgerCnt = (int)   FundTransaction::query()->whereBetween('created_at', [$from, $to])->count();

        $payoutsToProviders = $systemWalletId
            ? (float) FundTransaction::query()
                ->where('wallet_id', $systemWalletId)
                ->whereBetween('created_at', [$from, $to])
                ->where('direction', FundTransaction::DIRECTION_OUT)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->sum('amount')
            : 0.0;

        // ── Requests ──────────────────────────────────────────────────────────
        $requestsByStatus = RequestModel::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $requestsTotal     = (int) RequestModel::query()->whereBetween('created_at', [$from, $to])->count();
        $requestsFulfilled = (int) ($requestsByStatus['FULFILLED']  ?? 0);
        $requestsApproved  = (int) ($requestsByStatus['APPROVED']   ?? 0);
        $requestsRedeemable= (int) ($requestsByStatus['REDEEMABLE'] ?? 0);
        $requestsRejected  = (int) ($requestsByStatus['REJECTED']   ?? 0);
        $requestsCancelled = (int) ($requestsByStatus['CANCELLED']  ?? 0);
        $requestsPending   = (int) ($requestsByStatus['REQUESTED']  ?? 0);

        $requestsCityFund  = (int) RequestModel::query()->whereBetween('created_at', [$from, $to])->where('funding_source', 'CITY_FUND')->count();
        $requestsAdopted   = (int) RequestModel::query()->whereBetween('created_at', [$from, $to])->where('funding_source', 'PROVIDER_ADOPTION')->count();

        $requestsFulfilledAmt = (float) RequestModel::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'FULFILLED')
            ->sum('reserved_amount');

        // ── QR Redemptions ────────────────────────────────────────────────────
        $redemptionsTotal    = (int) OrderRedemption::query()->whereBetween('created_at', [$from, $to])->count();
        $redemptionsRedeemed = (int) OrderRedemption::query()->whereBetween('created_at', [$from, $to])->where('status', 'REDEEMED')->count();
        $redemptionsExpired  = (int) OrderRedemption::query()->whereBetween('created_at', [$from, $to])->where('status', 'EXPIRED')->count();
        $redemptionsPending  = (int) OrderRedemption::query()->whereBetween('created_at', [$from, $to])->where('status', 'PENDING')->count();

        // ── Active providers in period ────────────────────────────────────────
        $activeProviders = (int) User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->where('is_active', true)
            ->count();

        $providersWithRequests = (int) RequestModel::query()
            ->whereBetween('created_at', [$from, $to])
            ->distinct('provider_id')
            ->count('provider_id');

        $activeRecipients = (int) RequestModel::query()
            ->whereBetween('created_at', [$from, $to])
            ->distinct('recipient_id')
            ->count('recipient_id');

        return [
            // meta
            'from' => $from,
            'to'   => $to,

            // payments
            'payments_by_status'       => $paymentRows,
            'payments_total_count'     => $paymentsTotal,
            'payments_succeeded_count' => $paymentsSuccCnt,
            'payments_succeeded_amount'=> $paymentsSuccAmt,
            'payments_failed_count'    => $paymentsFailCnt,
            'payments_failed_amount'   => $paymentsFailAmt,
            'payments_pending_count'   => $paymentsPendCnt,

            // ledger
            'ledger_entries_count'  => $ledgerCnt,
            'ledger_in_amount'      => $ledgerIn,
            'ledger_out_amount'     => $ledgerOut,
            'ledger_net_amount'     => round($ledgerIn - $ledgerOut, 2),
            'payouts_to_providers'  => $payoutsToProviders,

            // requests
            'requests_total'          => $requestsTotal,
            'requests_fulfilled'      => $requestsFulfilled,
            'requests_approved'       => $requestsApproved,
            'requests_redeemable'     => $requestsRedeemable,
            'requests_pending'        => $requestsPending,
            'requests_rejected'       => $requestsRejected,
            'requests_cancelled'      => $requestsCancelled,
            'requests_city_fund'      => $requestsCityFund,
            'requests_adopted'        => $requestsAdopted,
            'requests_fulfilled_amount'=> $requestsFulfilledAmt,

            // redemptions
            'redemptions_total'    => $redemptionsTotal,
            'redemptions_redeemed' => $redemptionsRedeemed,
            'redemptions_expired'  => $redemptionsExpired,
            'redemptions_pending'  => $redemptionsPending,

            // participation
            'active_providers_total'       => $activeProviders,
            'providers_with_requests'      => $providersWithRequests,
            'active_recipients_with_requests' => $activeRecipients,
        ];
    }
}
