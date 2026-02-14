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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Scope to only active menu items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
