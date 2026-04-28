<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\ProviderPayout;
use App\Models\ProviderPayoutItem;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProviderPayoutSeeder extends Seeder
{
    public function run(): void
    {
        if (ProviderPayout::where('reference_number', 'like', 'DEMO-%')->exists()) {
            $this->command->info('⏭ Demo provider payouts already seeded.');

            return;
        }

        $providers = User::where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->where('is_active', true)
            ->get();

        if ($providers->isEmpty()) {
            $this->command->warn('⚠ No active providers found. Skipping.');

            return;
        }

        $adminUser = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        $payoutCount = 0;
        $itemCount = 0;

        foreach ($providers as $provider) {
            $profile = ProviderProfile::where('user_id', $provider->id)->first();
            if (! $profile) {
                continue;
            }

            $wallet = Ewallet::where('owner_type', 'PROVIDER')->where('owner_id', $profile->id)->first();
            if (! $wallet) {
                continue;
            }

            // Get the provider's earning IN transactions (from redemptions)
            $earningTxns = FundTransaction::where('wallet_id', $wallet->id)
                ->where('direction', FundTransaction::DIRECTION_IN)
                ->where('source', FundTransaction::SOURCE_REDEMPTION)
                ->orderBy('created_at')
                ->get();

            if ($earningTxns->isEmpty()) {
                continue;
            }

            // Create payouts in different statuses for this provider
            $statuses = [
                ProviderPayout::STATUS_TRANSFERRED,
                ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            ];

            // For the first provider with enough transactions, also add REJECTED and CANCELLED
            if ($earningTxns->count() >= 4 && $payoutCount === 0) {
                $statuses[] = ProviderPayout::STATUS_REJECTED;
                $statuses[] = ProviderPayout::STATUS_CANCELLED;
            }

            $txnCursor = 0;

            foreach ($statuses as $statusIdx => $status) {
                if ($txnCursor >= $earningTxns->count()) {
                    break;
                }

                // Take 1–3 transactions for this payout
                $take = min(rand(1, 3), $earningTxns->count() - $txnCursor);
                $payoutTxns = $earningTxns->slice($txnCursor, $take);
                $txnCursor += $take;

                $payoutAmount = $payoutTxns->sum('amount');
                $weekOffset = ($statusIdx + 1) * 7;

                $weekStart = now()->subDays($weekOffset + 6)->startOfDay();
                $weekEnd = now()->subDays($weekOffset)->endOfDay();
                $scheduledAt = $weekEnd->copy()->addDay();
                $createdAt = $scheduledAt->copy()->addHours(rand(1, 12));

                $payout = ProviderPayout::create([
                    'provider_id' => $provider->id,
                    'provider_wallet_id' => $wallet->id,
                    'week_start_at' => $weekStart,
                    'week_end_at' => $weekEnd,
                    'scheduled_at' => $scheduledAt,
                    'amount' => $payoutAmount,
                    'status' => $status,
                    'reference_number' => 'DEMO-PO-'.strtoupper(substr(md5(uniqid()), 0, 8)),
                    'receipt_path' => $status === ProviderPayout::STATUS_TRANSFERRED
                        ? 'payout_receipts/demo_receipt.pdf'
                        : null,
                    'admin_note' => match ($status) {
                        ProviderPayout::STATUS_REJECTED => 'Bank details need verification',
                        ProviderPayout::STATUS_CANCELLED => 'Provider requested cancellation',
                        default => null,
                    },
                    'confirmed_by' => $status === ProviderPayout::STATUS_TRANSFERRED ? $adminUser?->id : null,
                    'confirmed_at' => $status === ProviderPayout::STATUS_TRANSFERRED ? $createdAt->copy()->addHours(rand(2, 24)) : null,
                    'rejected_by' => $status === ProviderPayout::STATUS_REJECTED ? $adminUser?->id : null,
                    'rejected_at' => $status === ProviderPayout::STATUS_REJECTED ? $createdAt->copy()->addHours(rand(2, 24)) : null,
                    'cancelled_by' => $status === ProviderPayout::STATUS_CANCELLED ? $provider->id : null,
                    'cancelled_at' => $status === ProviderPayout::STATUS_CANCELLED ? $createdAt->copy()->addHours(rand(1, 6)) : null,
                    'snapshot_wallet_balance' => $wallet->balance,
                    'snapshot_available_amount' => $payoutAmount,
                    'meta' => json_encode(['demo_seed' => true]),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $payoutCount++;

                // Create payout items
                foreach ($payoutTxns as $txn) {
                    ProviderPayoutItem::create([
                        'provider_payout_id' => $payout->id,
                        'fund_transaction_id' => $txn->id,
                        'amount' => $txn->amount,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                    $itemCount++;
                }

                // For TRANSFERRED payouts, create the bank withdrawal OUT fund_transaction
                if ($status === ProviderPayout::STATUS_TRANSFERRED) {
                    FundTransaction::create([
                        'wallet_id' => $wallet->id,
                        'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
                        'amount' => $payoutAmount,
                        'direction' => FundTransaction::DIRECTION_OUT,
                        'provider_payout_id' => $payout->id,
                        'created_at' => $payout->confirmed_at,
                        'updated_at' => $payout->confirmed_at,
                    ]);
                }
            }
        }

        $this->command->info("✓ Seeded {$payoutCount} provider payouts, {$itemCount} payout items");
    }
}
