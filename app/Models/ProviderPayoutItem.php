<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderPayoutItem extends Model
{
    protected $fillable = [
        'provider_payout_id',
        'fund_transaction_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function providerPayout(): BelongsTo
    {
        return $this->belongsTo(ProviderPayout::class);
    }

    public function fundTransaction(): BelongsTo
    {
        return $this->belongsTo(FundTransaction::class);
    }
}
