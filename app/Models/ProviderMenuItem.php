<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderMenuItem extends Model
{
    protected $table = 'provider_menu_items';

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'price',
        'category',
        'sku',
        'max_per_request',
        'category_id',
        'is_active',
        'image_path',
        'is_admin_blocked',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_admin_blocked' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;
        if ($path === null || $path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);
        $segments = array_values(array_filter(explode('/', $normalized), static fn ($s) => $s !== ''));
        $encoded = array_map(static fn (string $segment): string => rawurlencode($segment), $segments);

        return asset('storage/'.implode('/', $encoded));
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function menuItemCategory(): BelongsTo
    {
        return $this->belongsTo(MenuItemCategory::class, 'category_id');
    }

    /**
     * Scope to only active menu items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_admin_blocked', false);
    }

    /**
     * Scope to items owned by a specific provider.
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('provider_id', $userId);
    }
}
