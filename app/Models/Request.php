<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'reserved_amount' => 'decimal:2',
        'is_flagged' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the recipient user.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the provider user.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the request items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    // Scopes
    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    public function scopePendingAdmin($query)
    {
        return $query->where('status', 'ADMIN_PENDING');
    }

    // Accessors
    public function getIsReservationActiveAttribute()
    {
        // Statuses that hold the allowance reservation
        return in_array($this->status, [
            'PENDING',
            'PROVIDER_APPROVED',
            'ADMIN_PENDING',
            'ADMIN_APPROVED',
            'REDEEMABLE',
            'FULFILLED'
        ]);
    }
}
