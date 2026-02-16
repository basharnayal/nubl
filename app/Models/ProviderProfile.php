<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name_ar',
        'full_name_en',
        'phone_number',
        'email',
        'business_name_ar',
        'business_name_en',
        'unified_number',
        'business_category',
        'address_ar',
        'address_en',
        'city',
        'region',
        'location',
    ];

    protected function casts(): array
    {
        return ['business_category' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function menuItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProviderMenuItem::class, 'provider_id', 'user_id');
    }
}
