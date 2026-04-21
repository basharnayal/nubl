<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FundTransaction extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('fund_transactions')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'wallet_id',
        'sponsor_id',
        'source',
        'amount',
        'direction',
        'payment_id',
        'request_id',
        'order_redemption_id',
        'provider_payout_id',
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

    /** External bank withdrawal from provider wallet after admin confirms transfer (distinct from internal city-fund → provider credit). */
    public const SOURCE_PROVIDER_BANK_PAYOUT = 'PROVIDER_BANK_PAYOUT';

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

    /**
     * Optional link when payout was triggered at QR redemption (FK not enforced in DB).
     */
    public function orderRedemption(): BelongsTo
    {
        return $this->belongsTo(OrderRedemption::class, 'order_redemption_id');
    }

    public function providerPayout(): BelongsTo
    {
        return $this->belongsTo(ProviderPayout::class);
    }
}
