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

        // ── Payments (gateway) — 7 queries → 1 ────────────────────────────
        $paymentRows = Payment::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(amount),0) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $paymentsTotal   = (int)   $paymentRows->sum('cnt');
        $paymentsSuccCnt = (int)   ($paymentRows->get(Payment::STATUS_SUCCEEDED)?->cnt   ?? 0);
        $paymentsSuccAmt = (float) ($paymentRows->get(Payment::STATUS_SUCCEEDED)?->total ?? 0);
        $paymentsFailCnt = (int)   ($paymentRows->get(Payment::STATUS_FAILED)?->cnt      ?? 0);
        $paymentsFailAmt = (float) ($paymentRows->get(Payment::STATUS_FAILED)?->total    ?? 0);
        $paymentsPendCnt = (int) (
            ($paymentRows->get(Payment::STATUS_INITIATED)?->cnt  ?? 0)
            + ($paymentRows->get(Payment::STATUS_PENDING)?->cnt  ?? 0)
            + ($paymentRows->get(Payment::STATUS_PROCESSING)?->cnt ?? 0)
        );

        // ── Fund ledger — 4 queries → 1 ────────────────────────────────────
        $systemWallet   = Ewallet::where('owner_type', 'SYSTEM')->first();
        $systemWalletId = $systemWallet?->id;

        $ledgerStats = FundTransaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw(
                'COUNT(*)                                                                                    AS entries_count,
                 COALESCE(SUM(CASE WHEN direction = ? THEN amount END), 0)                                  AS total_in,
                 COALESCE(SUM(CASE WHEN direction = ? THEN amount END), 0)                                  AS total_out,
                 COALESCE(SUM(CASE WHEN wallet_id = ? AND direction = ? AND source = ? THEN amount END), 0) AS provider_payouts',
                [
                    FundTransaction::DIRECTION_IN,
                    FundTransaction::DIRECTION_OUT,
                    $systemWalletId ?? 0, FundTransaction::DIRECTION_OUT, FundTransaction::SOURCE_PAYOUT,
                ]
            )
            ->first();

        $ledgerIn           = (float) ($ledgerStats->total_in          ?? 0);
        $ledgerOut          = (float) ($ledgerStats->total_out         ?? 0);
        $ledgerCnt          = (int)   ($ledgerStats->entries_count     ?? 0);
        $payoutsToProviders = (float) ($ledgerStats->provider_payouts  ?? 0);

        // ── Requests — 7 queries → 1 ───────────────────────────────────────
        $requestStats = RequestModel::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw(
                "COUNT(*)                                                                    AS total,
                 SUM(CASE WHEN status = 'FULFILLED'  THEN 1 ELSE 0 END)                    AS fulfilled,
                 SUM(CASE WHEN status = 'APPROVED'   THEN 1 ELSE 0 END)                    AS approved,
                 SUM(CASE WHEN status = 'REDEEMABLE' THEN 1 ELSE 0 END)                    AS redeemable,
                 SUM(CASE WHEN status = 'REJECTED'   THEN 1 ELSE 0 END)                    AS rejected,
                 SUM(CASE WHEN status = 'CANCELLED'  THEN 1 ELSE 0 END)                    AS cancelled,
                 SUM(CASE WHEN status = 'REQUESTED'  THEN 1 ELSE 0 END)                    AS pending,
                 SUM(CASE WHEN funding_source = 'CITY_FUND' THEN 1 ELSE 0 END)             AS city_fund,
                 SUM(CASE WHEN funding_source = 'PROVIDER_ADOPTION' THEN 1 ELSE 0 END)     AS adopted,
                 COALESCE(SUM(CASE WHEN status = 'FULFILLED' THEN reserved_amount END), 0)  AS fulfilled_amount,
                 COUNT(DISTINCT provider_id)                                                 AS providers_with_requests,
                 COUNT(DISTINCT recipient_id)                                                AS active_recipients"
            )
            ->first();

        // ── QR Redemptions — 4 queries → 1 ─────────────────────────────────
        $redemptionStats = OrderRedemption::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw(
                "COUNT(*)                                                AS total,
                 SUM(CASE WHEN status = 'REDEEMED' THEN 1 ELSE 0 END)  AS redeemed,
                 SUM(CASE WHEN status = 'EXPIRED'  THEN 1 ELSE 0 END)  AS expired,
                 SUM(CASE WHEN status = 'PENDING'  THEN 1 ELSE 0 END)  AS pending"
            )
            ->first();

        // ── Active providers (not date-bound) ───────────────────────────────
        $activeProviders = (int) User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->where('is_active', true)
            ->count();

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
            'requests_total'           => (int)   ($requestStats->total                  ?? 0),
            'requests_fulfilled'       => (int)   ($requestStats->fulfilled              ?? 0),
            'requests_approved'        => (int)   ($requestStats->approved               ?? 0),
            'requests_redeemable'      => (int)   ($requestStats->redeemable             ?? 0),
            'requests_pending'         => (int)   ($requestStats->pending                ?? 0),
            'requests_rejected'        => (int)   ($requestStats->rejected               ?? 0),
            'requests_cancelled'       => (int)   ($requestStats->cancelled              ?? 0),
            'requests_city_fund'       => (int)   ($requestStats->city_fund              ?? 0),
            'requests_adopted'         => (int)   ($requestStats->adopted                ?? 0),
            'requests_fulfilled_amount'=> (float) ($requestStats->fulfilled_amount       ?? 0),

            // redemptions
            'redemptions_total'    => (int) ($redemptionStats->total    ?? 0),
            'redemptions_redeemed' => (int) ($redemptionStats->redeemed ?? 0),
            'redemptions_expired'  => (int) ($redemptionStats->expired  ?? 0),
            'redemptions_pending'  => (int) ($redemptionStats->pending  ?? 0),

            // participation
            'active_providers_total'          => $activeProviders,
            'providers_with_requests'         => (int) ($requestStats->providers_with_requests  ?? 0),
            'active_recipients_with_requests' => (int) ($requestStats->active_recipients       ?? 0),
        ];
    }
}
