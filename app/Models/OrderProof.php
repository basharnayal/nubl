<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProof extends Model
{
    protected $fillable = [
        'order_redemption_id',
        'proof_url',
        'is_provider_donation',
        'fulfilled_at',
    ];

    protected $casts = [
        'is_provider_donation' => 'boolean',
        'fulfilled_at' => 'datetime',
    ];

    public function orderRedemption(): BelongsTo
    {
        return $this->belongsTo(OrderRedemption::class, 'order_redemption_id');
    }
}
