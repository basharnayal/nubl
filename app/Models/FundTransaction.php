<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'sponsor_id',
        'source',
        'amount',
        'direction',
        'payment_id',
        'request_id',
        'order_redemption_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public const SOURCE_DONATION = 'DONATION';
    public const SOURCE_REDEMPTION = 'REDEMPTION';
    public const SOURCE_REFUND = 'REFUND';
    public const SOURCE_EXPIRY_ROLLBACK = 'EXPIRY_ROLLBACK';
    public const SOURCE_CANCELLED = 'CANCELLED';
    public const SOURCE_PAYOUT = 'PAYOUT';

    public const DIRECTION_IN = 'IN';
    public const DIRECTION_OUT = 'OUT';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Ewallet::class, 'wallet_id');
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
