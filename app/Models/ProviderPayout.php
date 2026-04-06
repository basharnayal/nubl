<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderPayout extends Model
{
    public const STATUS_PENDING_ADMIN_REVIEW = 'PENDING_ADMIN_REVIEW';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_TRANSFERRED = 'TRANSFERRED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    /** Statuses that reserve linked earning IN lines for settlement. */
    public const RESERVING_STATUSES = [
        self::STATUS_PENDING_ADMIN_REVIEW,
        self::STATUS_PROCESSING,
        self::STATUS_TRANSFERRED,
    ];

    protected $fillable = [
        'provider_id',
        'provider_wallet_id',
        'week_start_at',
        'week_end_at',
        'scheduled_at',
        'amount',
        'status',
        'reference_number',
        'receipt_path',
        'admin_note',
        'confirmed_by',
        'confirmed_at',
        'rejected_by',
        'rejected_at',
        'cancelled_by',
        'cancelled_at',
        'snapshot_wallet_balance',
        'snapshot_available_amount',
        'meta',
        'fund_transaction_out_id',
    ];

    protected function casts(): array
    {
        return [
            'week_start_at' => 'datetime',
            'week_end_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'amount' => 'decimal:2',
            'snapshot_wallet_balance' => 'decimal:2',
            'snapshot_available_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function providerWallet(): BelongsTo
    {
        return $this->belongsTo(Ewallet::class, 'provider_wallet_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProviderPayoutItem::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function fundTransactionOut(): BelongsTo
    {
        return $this->belongsTo(FundTransaction::class, 'fund_transaction_out_id');
    }

    public function isConfirmable(): bool
    {
        return $this->status === self::STATUS_PENDING_ADMIN_REVIEW;
    }
}
