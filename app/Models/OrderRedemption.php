<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderRedemption extends Model
{
    protected $fillable = [
        'request_id',
        'provider_id',
        'token_code',
        'short_code_hash',
        'token_ciphertext',
        'short_code_ciphertext',
        'token_qr_url',
        'ttl_minutes',
        'redeem_expires_at',
        'status',
    ];

    protected $casts = [
        'redeem_expires_at' => 'datetime',
        'ttl_minutes' => 'integer',
        'token_ciphertext' => 'encrypted',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function proof(): HasOne
    {
        return $this->hasOne(OrderProof::class, 'order_redemption_id');
    }
}
