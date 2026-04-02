<?php

namespace App\Observers;

use App\Http\Services\AuditService;
use App\Models\FundTransaction;

class FundTransactionObserver
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Update wallet balance and write an audit entry when a FundTransaction is created.
     * FR-13.1: record every transaction state change with actor ID and timestamp.
     * IN -> increment balance, OUT -> decrement balance.
     */
    public function created(FundTransaction $transaction): void
    {
        $wallet = $transaction->wallet;
        if (! $wallet) {
            return;
        }

        $amount = (float) $transaction->amount;
        if ($amount <= 0) {
            return;
        }

        if ($transaction->direction === FundTransaction::DIRECTION_IN) {
            $wallet->increment('balance', $amount);
        } else {
            $wallet->decrement('balance', $amount);
        }

        // FR-13.1: audit every ledger movement with actor + timestamp
        $this->auditService->log('fund_transaction', 'created', [
            'fund_transaction_id'  => $transaction->id,
            'wallet_id'            => $transaction->wallet_id,
            'direction'            => $transaction->direction,
            'source'               => $transaction->source,
            'amount'               => $amount,
            'payment_id'           => $transaction->payment_id,
            'request_id'           => $transaction->request_id,
            'order_redemption_id'  => $transaction->order_redemption_id,
        ], $transaction->sponsor_id ?? auth()->id());
    }
}
