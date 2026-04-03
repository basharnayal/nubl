<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores allocations queued while the engine is globally or per-provider paused (FR-24.2).
 */
class PendingAllocation extends Model
{
    protected $fillable = [
        'request_id',
        'provider_id',
        'amount',
        'paused_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
