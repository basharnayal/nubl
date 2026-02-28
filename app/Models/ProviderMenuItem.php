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
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
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
        return $query->where('is_active', true);
    }

    /**
     * Scope to items owned by a specific provider.
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('provider_id', $userId);
    }
}
