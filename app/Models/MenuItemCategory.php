<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemCategory extends Model
{
    protected $fillable = [
        'business_category',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function providerMenuItems()
    {
        return $this->hasMany(ProviderMenuItem::class, 'category_id');
    }
}
