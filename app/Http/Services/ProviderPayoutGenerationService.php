<?php

namespace App\Http\Services;

use App\Models\Ewallet;
use App\Models\ProviderPayout;
use App\Models\ProviderPayoutItem;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Notifications\ProviderPayoutPendingAdminNotification;
use App\Support\ProviderPayoutWeek;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProviderPayoutGenerationService
{
    public function __construct(
        private ProviderPayoutLedgerService $ledgerService,
        private AuditService $auditService
    ) {}

    /**
     * Anti-duplication (weekly run):
     * - Cache lock `provider-payout-generate-weekly` serializes concurrent command runs (pairs with schedule withoutOverlapping).
     * - Per provider: wallet row lock + transaction, then {@see hasBlockingPayoutForWeek} blocks a second payout for the same settlement window while one is still pending/processing/transferred.
     * - Ledger: {@see ProviderPayoutLedgerService::eligibleEarningInQuery} excludes earning IN lines already tied to a payout in RESERVING_STATUSES.
     * - DB: unique (provider_payout_id, fund_transaction_id) on provider_payout_items; unique fund_transaction_out_id on provider_payouts.
     *
     * @return list<int> IDs of created provider_payouts
     */
    public function generateWeeklyAt(Carbon $runAt): array
    {
        $lock = Cache::lock('provider-payout-generate-weekly', 600);

        if (! $lock->get()) {
            return [];
        }

        try {
            return $this->runGeneration($runAt);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return list<int>
     */
    private function runGeneration(Carbon $runAt): array
    {
        $boundaries = ProviderPayoutWeek::settlementWeekBoundariesAt($runAt);
        $weekStart = $boundaries['week_start_at'];
        $weekEnd = $boundaries['week_end_at'];
        $scheduledAt = $runAt->copy();

        $minAmount = (string) number_format((float) config('provider_payout.min_weekly_payout_amount', 50), 2, '.', '');

        $createdIds = [];

        $profiles = ProviderProfile::query()
            ->whereHas('user', function ($q) {
                $q->where('status', User::STATUS_ACTIVE)
                    ->role('provider');
            })
            ->with('ewallet')
            ->get();

        foreach ($profiles as $profile) {
            $wallet = $profile->ewallet;
            if (! $wallet || ! $wallet->status) {
                continue;
            }

            DB::transaction(function () use ($profile, $wallet, $weekStart, $weekEnd, $scheduledAt, $minAmount, &$createdIds) {
                Ewallet::query()->whereKey($wallet->id)->lockForUpdate()->first();

                if ($this->hasBlockingPayoutForWeek($profile->user_id, $weekStart, $weekEnd)) {
                    return;
                }

                $eligible = $this->ledgerService->eligibleEarningTransactions($wallet);
                if ($eligible->isEmpty()) {
                    return;
                }

                $total = $this->ledgerService->sumDecimalAmounts($eligible->pluck('amount')->all());
                if ($this->ledgerService->compare($total, $minAmount) < 0) {
                    return;
                }

                $snapshotBalance = $this->ledgerService->roundDecimal((string) $wallet->fresh()->balance);
                $snapshotAvailable = $total;

                $payout = ProviderPayout::query()->create([
                    'provider_id' => $profile->user_id,
                    'provider_wallet_id' => $wallet->id,
                    'week_start_at' => $weekStart,
                    'week_end_at' => $weekEnd,
                    'scheduled_at' => $scheduledAt,
                    'amount' => $total,
                    'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
                    'snapshot_wallet_balance' => $snapshotBalance,
                    'snapshot_available_amount' => $snapshotAvailable,
                    'meta' => [
                        'eligible_fund_transaction_ids' => $eligible->pluck('id')->values()->all(),
                    ],
                ]);

                foreach ($eligible as $ft) {
                    ProviderPayoutItem::query()->create([
                        'provider_payout_id' => $payout->id,
                        'fund_transaction_id' => $ft->id,
                        'amount' => $ft->amount,
                    ]);
                }

                $this->auditService->log('provider_payout', 'payout_request_created', [
                    'provider_payout_id' => $payout->id,
                    'provider_id' => $profile->user_id,
                    'amount' => $total,
                    'week_start_at' => $weekStart->toIso8601String(),
                    'week_end_at' => $weekEnd->toIso8601String(),
                ]);

                $createdIds[] = $payout->id;
            });
        }

        foreach ($createdIds as $payoutId) {
            $this->notifyAdminsOfNewPayout($payoutId);
        }

        return $createdIds;
    }

    private function hasBlockingPayoutForWeek(int $providerUserId, Carbon $weekStart, Carbon $weekEnd): bool
    {
        return ProviderPayout::query()
            ->where('provider_id', $providerUserId)
            ->where('week_start_at', $weekStart->toDateTimeString())
            ->where('week_end_at', $weekEnd->toDateTimeString())
            ->whereIn('status', [
                ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
                ProviderPayout::STATUS_PROCESSING,
                ProviderPayout::STATUS_TRANSFERRED,
            ])
            ->exists();
    }

    private function notifyAdminsOfNewPayout(int $payoutId): void
    {
        $payout = ProviderPayout::query()->with('provider')->find($payoutId);
        if (! $payout) {
            return;
        }

        User::role('admin')->get()->each(
            fn (User $admin) => $admin->notify(new ProviderPayoutPendingAdminNotification($payout))
        );
    }
}
