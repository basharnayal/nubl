<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientKycDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'income_band',
        'household_size',
        'marital_status',
        'is_student',
        'employment_status',
        'situation_description',
    ];

    protected function casts(): array
    {
        return [
            'is_student' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const MARITAL_STATUSES = ['single', 'married', 'divorced', 'widowed'];

    public const INCOME_BANDS = [
        '0-500', '500-1000', '1000-1500', '1500-2000',
        '2000-2500', '2500-3000', '3000-5000', '5000+',
    ];

    public const EMPLOYMENT_STATUSES = [
        'unemployed',
        'unable_to_work',
        'employed_insufficient_income',
    ];
}
