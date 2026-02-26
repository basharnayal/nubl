<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function ewallet(): HasOne
    {
        return $this->hasOne(Ewallet::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function menuItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProviderMenuItem::class, 'provider_id', 'user_id');
    }

    protected static function booted(): void
    {
        static::created(function (self $profile) {
            if (! $profile->ewallet) {
                $profile->ewallet()->create([
                    'owner_type' => 'PROVIDER',
                    'balance' => 0,
                    'status' => true,
                ]);
            }
        });
    }
}
