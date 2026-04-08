<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\WeeklyAllowanceNotification;
use App\Support\UiDateTime;
use App\Support\WeeklyAllowanceSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RecipientAllowanceService
{
    /**
     * FR-17.1: Active weekly limit from system_settings when set; scheduled pending
     * applies from next week boundary. Falls back to config default.
     */
    public static function weeklyLimit(): float
    {
        self::maybePromotePendingAllowance();

        $active = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_ACTIVE);
        if ($active !== null && $active !== '') {
            return (float) $active;
        }

        return (float) config('recipient.weekly_allowance_limit', 400);
    }

    /**
     * When pending effective time has passed, promote pending value to active and clear pending keys.
     */
    public static function maybePromotePendingAllowance(): void
    {
        $pending = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE);
        $effectiveRaw = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT);

        if ($pending === null || $pending === '' || $effectiveRaw === null || $effectiveRaw === '') {
            return;
        }

        $effectiveAt = Carbon::parse($effectiveRaw)->timezone(config('app.timezone'));

        if (Carbon::now()->timezone(config('app.timezone'))->greaterThanOrEqualTo($effectiveAt)) {
            SystemSetting::setValue(WeeklyAllowanceSettings::KEY_ACTIVE, $pending);
            WeeklyAllowanceSettings::clearPending();

            Notification::send(
                User::role('recipient')->get(),
                new WeeklyAllowanceNotification('applied', (float) $pending)
            );
        }
    }

    /**
     * Data for the admin weekly allowance settings screen (FR-17.1).
     *
     * @return array<string, mixed>
     */
    public static function adminAllowanceSettingsPageData(): array
    {
        $effectiveLimit = self::weeklyLimit();
        $pendingValue = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE);
        $pendingEffectiveRaw = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT);

        $pendingEffectiveAt = null;
        if ($pendingEffectiveRaw) {
            $pendingEffectiveAt = Carbon::parse($pendingEffectiveRaw)->timezone(config('app.timezone'));
        }

        $min = (int) config('recipient.weekly_allowance_limit_min', 1);
        $max = (int) config('recipient.weekly_allowance_limit_max', 100_000);
        $default = (float) config('recipient.weekly_allowance_limit', 400);

        $nextBoundary = WeeklyAllowanceSettings::nextEffectiveBoundary();

        $pendingEffectiveFormatted = $pendingEffectiveAt
            ? UiDateTime::mediumWith12h($pendingEffectiveAt)
            : null;
        $nextBoundaryFormatted = UiDateTime::mediumWith12h($nextBoundary);
        $appTimezone = config('app.timezone');

        return compact(
            'effectiveLimit',
            'pendingValue',
            'pendingEffectiveAt',
            'pendingEffectiveFormatted',
            'min',
            'max',
            'default',
            'nextBoundary',
            'nextBoundaryFormatted',
            'appTimezone'
        );
    }

    /**
     * Persist a pending weekly limit for the next boundary and notify recipients (FR-17.1).
     */
    public static function schedulePendingWeeklyAllowanceChange(float $amount, User $actor): void
    {
        $value = number_format($amount, 2, '.', '');
        $effectiveAt = WeeklyAllowanceSettings::nextEffectiveBoundary();

        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE, $value);
        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT, $effectiveAt->toIso8601String());

        Notification::send(
            User::role('recipient')->get(),
            new WeeklyAllowanceNotification(
                'scheduled',
                (float) $value,
                $effectiveAt->toIso8601String(),
                $actor
            )
        );
    }

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

        return max(0, self::weeklyLimit() - $weeklyUsed);
    }

    /**
     * Check if adding amount A would exceed the weekly allowance.
     */
    public static function wouldExceedAllowance(int $recipientId, float $amount): bool
    {
        $weeklyUsed = self::getWeeklyUsed($recipientId);

        return ($weeklyUsed + $amount) > self::weeklyLimit();
    }
}
