<?php

namespace App\Models;

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
    public function getBalanceFromTransactions(): float
    {
        $in = $this->fundTransactions()->where('direction', 'IN')->sum('amount');
        $out = $this->fundTransactions()->where('direction', 'OUT')->sum('amount');

        return (float) ($in - $out);
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

