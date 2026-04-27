<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nationality',
        'short_address',
        'location',
        'id_type',
        'id_number',
        'id_photo_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const ID_TYPES = ['national_id', 'iqama', 'hudood_number'];
}
