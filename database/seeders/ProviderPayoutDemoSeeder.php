<?php

namespace Database\Seeders;

use App\Models\FundTransaction;
use App\Models\ProviderPayout;
use App\Models\ProviderPayoutItem;
use App\Models\User;
use App\Support\ProviderPayoutWeek;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds demo wallet earnings + provider payout rows for local QA (admin queue + provider wallet UI).
 *
 * Targets provider user id 3 (Al-Rashid Kitchen / provider@nubl.com) when present; otherwise falls back to that email.
 *
 * Run: php artisan db:seed --class=ProviderPayoutDemoSeeder
 */
class ProviderPayoutDemoSeeder extends Seeder
{
    public function run(): void
    {
        $provider = User::query()->find(3);
        if (! $provider || ! $provider->hasRole('provider')) {
            $provider = User::query()->where('email', 'provider@nubl.com')->first();
        }

        if (! $provider || ! $provider->hasRole('provider')) {
            $this->command?->warn('Provider user id 3 / provider@nubl.com not found or not a provider. Skipping.');

            return;
        }

        if (ProviderPayout::query()
            ->where('provider_id', $provider->id)
            ->where('meta->demo_seed', true)
            ->exists()) {
            $this->command?->warn('Demo payouts already seeded for this provider. Delete those rows or clear meta to re-run.');

            return;
        }

        $profile = $provider->providerProfile;
        if (! $profile) {
            $this->command?->error('Provider profile missing. Run ProviderSeeder first.');

            return;
        }

        $wallet = $profile->ewallet ?? $profile->ewallet()->create([
            'owner_type' => 'PROVIDER',
            'balance' => 0,
            'status' => true,
        ]);
        $admin = User::role('admin')->first();

        $runAt = Carbon::now()->timezone(config('app.timezone'));
        $bounds = ProviderPayoutWeek::settlementWeekBoundariesAt($runAt);

        $demoReceiptRelative = 'provider-payout-receipts/demo-seed/transfer-demo.txt';
        Storage::disk('local')->put($demoReceiptRelative, "Demo bank transfer receipt\nReference: DEMO-REF-SEED-001\nProvider: {$provider->email}\n");

        DB::transaction(function () use ($provider, $wallet, $admin, $runAt, $bounds, $demoReceiptRelative) {
            $ft1 = FundTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_PAYOUT,
                'amount' => 75.00,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => null,
            ]);

            $ft2 = FundTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_PAYOUT,
                'amount' => 75.00,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => null,
            ]);

            $ft3 = FundTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_PAYOUT,
                'amount' => 60.00,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => null,
            ]);

            $wallet->refresh();

            $pending = ProviderPayout::query()->create([
                'provider_id' => $provider->id,
                'provider_wallet_id' => $wallet->id,
                'week_start_at' => $bounds['week_start_at'],
                'week_end_at' => $bounds['week_end_at'],
                'scheduled_at' => $runAt,
                'amount' => $ft1->amount + $ft2->amount,
                'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
                'reference_number' => null,
                'receipt_path' => null,
                'admin_note' => 'تجربة: طلب تحويل معلق لمراجعة الأدمن (بيانات تجريبية).',
                'snapshot_wallet_balance' => $wallet->balance,
                'snapshot_available_amount' => $ft1->amount + $ft2->amount,
                'meta' => [
                    'demo_seed' => true,
                    'label' => 'ProviderPayoutDemoSeeder',
                ],
            ]);

            ProviderPayoutItem::query()->create([
                'provider_payout_id' => $pending->id,
                'fund_transaction_id' => $ft1->id,
                'amount' => $ft1->amount,
            ]);
            ProviderPayoutItem::query()->create([
                'provider_payout_id' => $pending->id,
                'fund_transaction_id' => $ft2->id,
                'amount' => $ft2->amount,
            ]);

            $transferred = ProviderPayout::query()->create([
                'provider_id' => $provider->id,
                'provider_wallet_id' => $wallet->id,
                'week_start_at' => $bounds['week_start_at']->copy()->subWeek(),
                'week_end_at' => $bounds['week_end_at']->copy()->subWeek(),
                'scheduled_at' => $runAt->copy()->subWeek(),
                'amount' => $ft3->amount,
                'status' => ProviderPayout::STATUS_TRANSFERRED,
                'reference_number' => 'DEMO-REF-SEED-001',
                'receipt_path' => $demoReceiptRelative,
                'admin_note' => 'تجربة: تم التحويل (بيانات تجريبية). يمكنك تحميل الإيصال أدناه.',
                'confirmed_by' => $admin?->id,
                'confirmed_at' => now()->subDays(2),
                'snapshot_wallet_balance' => $wallet->balance,
                'snapshot_available_amount' => $ft3->amount,
                'meta' => [
                    'demo_seed' => true,
                    'label' => 'ProviderPayoutDemoSeeder',
                ],
            ]);

            ProviderPayoutItem::query()->create([
                'provider_payout_id' => $transferred->id,
                'fund_transaction_id' => $ft3->id,
                'amount' => $ft3->amount,
            ]);

            $out = FundTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
                'amount' => $ft3->amount,
                'direction' => FundTransaction::DIRECTION_OUT,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => $transferred->id,
            ]);

            $transferred->update(['fund_transaction_out_id' => $out->id]);
        });

        $wallet->refresh();

        $this->command?->info("Demo provider payouts seeded for user #{$provider->id} ({$provider->email}). wallet balance: {$wallet->balance} SAR");
    }
}
