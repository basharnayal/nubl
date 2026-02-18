<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'menu_item_id',
        'quantity',
        'price_snapshot',
        'line_total',
    ];


    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(ProviderMenuItem::class, 'menu_item_id');
    }
}
