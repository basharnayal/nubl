<?php

namespace App\Models;

use App\Support\FinancialMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ewallet extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Balance is kept in sync by FundTransactionObserver (updated on each new FundTransaction).
     * Fallback: calculate from fund_transactions if needed.
     */
    public function getBalanceFromTransactions(): string
    {
        $in = (string) $this->fundTransactions()->where('direction', FundTransaction::DIRECTION_IN)->sum('amount');
        $out = (string) $this->fundTransactions()->where('direction', FundTransaction::DIRECTION_OUT)->sum('amount');

        return FinancialMath::sub(FinancialMath::normalize($in), FinancialMath::normalize($out));
    }

    /**
     * Recalculate balance from fund_transactions and update the balance column.
     */
    public function syncBalance(): void
    {
        $this->update(['balance' => $this->getBalanceFromTransactions()]);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'owner_id');
    }

    public function fundTransactions(): HasMany
    {
        return $this->hasMany(FundTransaction::class, 'wallet_id');
    }
}

