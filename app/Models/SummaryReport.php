<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FR-19.1: Persisted auto-generated weekly / monthly financial summary report.
 */
class SummaryReport extends Model
{
    protected $fillable = [
        'type',
        'period_from',
        'period_to',
        'payload',
        'generated_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'payload' => 'array',
        'generated_at' => 'datetime',
    ];

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_MONTHLY = 'monthly';
}
