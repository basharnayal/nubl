<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderFinancialInfo extends Model
{
    protected $table = 'provider_financial_info';

    protected $fillable = [
        'user_id',
        'bank_name',
        'iban',
        'account_holder_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
