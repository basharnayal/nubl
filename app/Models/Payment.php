<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('payments')
            ->dontSubmitEmptyLogs();
    }

    public const STATUS_INITIATED = 'INITIATED';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_SUCCEEDED = 'SUCCEEDED';

    public const STATUS_FAILED = 'FAILED';

    public const GATEWAY_MYFATOORAH = 'MYFATOORAH';

    protected $fillable = [
        'sponsor_id',
        'gateway',
        'external_payment_id',
        'status',
        'amount',
        'notes',
        'idempotency_key',
        'is_guest',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'notes' => 'array',
        'created_at' => 'datetime',
        'is_guest' => 'boolean',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function fundTransactions(): HasMany
    {
        return $this->hasMany(FundTransaction::class);
    }

    public function requestPaymentLinks(): HasMany
    {
        return $this->hasMany(RequestPaymentLink::class);
    }
}
