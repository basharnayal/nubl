<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderOperatingInfo extends Model
{
    protected $table = 'provider_operating_info';

    protected $fillable = [
        'user_id',
        'operating_hours',
        'daily_capacity',
        'service_type',
        'estimated_preparation_order_time',
        'adoption_support',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'service_type' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
