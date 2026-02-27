<?php

namespace App\Observers;

use App\Models\FundTransaction;

class FundTransactionObserver
{
    /**
     * Update wallet balance when a FundTransaction is created.
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
    }
}
