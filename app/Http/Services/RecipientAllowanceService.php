<?php

namespace App\Http\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecipientAllowanceService
{
    public const WEEKLY_LIMIT = 400;

    /**
     * Week definition: Sunday 00:00 – Saturday 23:59:59.
     * Limit resets implicitly at the start of each new week.
     */
    public static function getCurrentWeekBounds(): array
    {
        $now = Carbon::now();
        return [
            $now->copy()->startOfWeek(Carbon::SUNDAY),
            $now->copy()->endOfWeek(Carbon::SATURDAY),
        ];
    }

    /**
     * Sum of (price_snapshot * quantity) for this recipient this week
     * where request status IN ('REQUESTED', 'REDEEMABLE', 'FULFILLED')
     * and the order consumes the city weekly allowance (not provider adoption).
     * Matches order_proofs.is_provider_donation: when true, the amount must not count here.
     */
    public static function getWeeklyUsed(int $recipientId): float
    {
        [$weekStart, $weekEnd] = self::getCurrentWeekBounds();

        $total = DB::table('request_items')
            ->join('requests', 'request_items.request_id', '=', 'requests.id')
            ->where('requests.recipient_id', $recipientId)
            ->whereBetween('requests.created_at', [$weekStart, $weekEnd])
            ->whereIn('requests.status', ['REQUESTED', 'REDEEMABLE', 'FULFILLED'])
            ->where(function ($q) {
                $q->whereNull('requests.funding_source')
                    ->orWhere('requests.funding_source', '!=', 'PROVIDER_ADOPTION');
            })
            ->selectRaw('COALESCE(SUM(request_items.price_snapshot * request_items.quantity), 0) as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    /**
     * Remaining weekly allowance (400 - weekly_used), minimum 0.
     */
    public static function getRemainingLimit(int $recipientId): float
    {
        $weeklyUsed = self::getWeeklyUsed($recipientId);
        return max(0, self::WEEKLY_LIMIT - $weeklyUsed);
    }

    /**
     * Check if adding amount A would exceed the weekly allowance.
     */
    public static function wouldExceedAllowance(int $recipientId, float $amount): bool
    {
        $weeklyUsed = self::getWeeklyUsed($recipientId);
        return ($weeklyUsed + $amount) > self::WEEKLY_LIMIT;
    }
}
