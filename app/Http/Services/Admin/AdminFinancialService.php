<?php

namespace App\Http\Services\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
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
     * @return array<string, mixed>
     */
    public function getRangeSummary(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $paymentRows = Payment::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(amount),0) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $ledgerIn = (float) FundTransaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('direction', FundTransaction::DIRECTION_IN)
            ->sum('amount');

        $ledgerOut = (float) FundTransaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('direction', FundTransaction::DIRECTION_OUT)
            ->sum('amount');

        $ledgerCount = (int) FundTransaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return [
            'from' => $from,
            'to' => $to,
            'payments_by_status' => $paymentRows,
            'payments_total_count' => (int) Payment::query()->whereBetween('created_at', [$from, $to])->count(),
            'payments_succeeded_amount' => (float) Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->sum('amount'),
            'payments_failed_amount' => (float) Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', Payment::STATUS_FAILED)
                ->sum('amount'),
            'ledger_entries_count' => $ledgerCount,
            'ledger_in_amount' => $ledgerIn,
            'ledger_out_amount' => $ledgerOut,
        ];
    }
}
