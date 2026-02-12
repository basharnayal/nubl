<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientKycDetails extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'income_band',
        'household_size',
        'marital_status',
        'is_student',
        'address_confirmation',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_student' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the KYC details.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Valid marital status values.
     */
    public const MARITAL_STATUSES = ['single', 'married', 'divorced', 'widowed'];

    /**
     * Valid income band values (monthly SAR ranges).
     */
    public const INCOME_BANDS = [
        '0-500',
        '500-1000',
        '1000-1500',
        '1500-2000',
        '2000-2500',
        '2500-3000',
        '3000-5000',
        '5000+',
    ];
}
