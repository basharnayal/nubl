<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    /** Core request statuses: REQUESTED → APPROVED | REDEEMABLE | REJECTED → FULFILLED (no transition logic for FULFILLED) */
    public const STATUSES = ['REQUESTED', 'APPROVED', 'REDEEMABLE', 'REJECTED', 'FULFILLED'];

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

    /**
     * Get the request payment links (allocation from payments to this request).
     */
    public function requestPaymentLinks(): HasMany
    {
        return $this->hasMany(RequestPaymentLink::class);
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
            'REQUESTED',
            'APPROVED',
            'ADMIN_PENDING',
            'ADMIN_APPROVED',
            'REDEEMABLE',
            'FULFILLED'
        ]);
    }
}
