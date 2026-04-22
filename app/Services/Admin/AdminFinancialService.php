<?php

namespace App\Services\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Support\FinancialMath;
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
        $systemWallet   = Ewallet::where('owner_type', 'SYSTEM')->first();
        $systemWalletId = $systemWallet?->id;

        // 6 individual Payment queries → 1 aggregate query
        [$pi, $pp, $pr] = [Payment::STATUS_INITIATED, Payment::STATUS_PENDING, Payment::STATUS_PROCESSING];

        $paymentStats = Payment::query()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)                       AS succeeded_count,
                 COALESCE(SUM(CASE WHEN status = ? THEN amount END), 0)             AS succeeded_amount,
                 SUM(CASE WHEN status IN (?,?,?) THEN 1 ELSE 0 END)                AS pending_count,
                 COALESCE(SUM(CASE WHEN status IN (?,?,?) THEN amount END), 0)     AS pending_amount,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)                       AS failed_count,
                 COALESCE(SUM(CASE WHEN status = ? THEN amount END), 0)            AS failed_amount',
                [
                    Payment::STATUS_SUCCEEDED,
                    Payment::STATUS_SUCCEEDED,
                    $pi, $pp, $pr,
                    $pi, $pp, $pr,
                    Payment::STATUS_FAILED,
                    Payment::STATUS_FAILED,
                ]
            )
            ->first();

        // 3 individual FundTransaction queries → 1 aggregate query
        $ledgerStats = $systemWalletId
            ? FundTransaction::query()
                ->where('wallet_id', $systemWalletId)
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN direction = ? THEN amount END), 0)                AS fund_in,
                     COALESCE(SUM(CASE WHEN direction = ? THEN amount END), 0)                AS fund_out,
                     COALESCE(SUM(CASE WHEN direction = ? AND source = ? THEN amount END), 0) AS provider_payouts',
                    [
                        FundTransaction::DIRECTION_IN,
                        FundTransaction::DIRECTION_OUT,
                        FundTransaction::DIRECTION_OUT, FundTransaction::SOURCE_PAYOUT,
                    ]
                )
                ->first()
            : null;

        return [
            'system_wallet_balance'      => $systemWallet ? (float) $systemWallet->balance : 0.0,
            'successful_payments_count'  => (int)   ($paymentStats->succeeded_count  ?? 0),
            'successful_payments_amount' => (float) ($paymentStats->succeeded_amount ?? 0),
            'pending_count'              => (int)   ($paymentStats->pending_count    ?? 0),
            'pending_amount'             => (float) ($paymentStats->pending_amount   ?? 0),
            'failed_count'               => (int)   ($paymentStats->failed_count     ?? 0),
            'failed_amount'              => (float) ($paymentStats->failed_amount    ?? 0),
            'fund_inbound_system'        => (float) ($ledgerStats->fund_in           ?? 0),
            'fund_outbound_system'       => (float) ($ledgerStats->fund_out          ?? 0),
            'transfers_to_providers'     => (float) ($ledgerStats->provider_payouts  ?? 0),
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
            'ledger_net_amount'     => (float) FinancialMath::sub(
                FinancialMath::normalize((string) $ledgerIn),
                FinancialMath::normalize((string) $ledgerOut)
            ),
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
