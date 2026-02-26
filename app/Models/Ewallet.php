<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'owner_id');
    }
}

