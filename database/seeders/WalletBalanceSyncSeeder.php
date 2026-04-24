<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use Illuminate\Database\Seeder;

class WalletBalanceSyncSeeder extends Seeder
{
    public function run(): void
    {
        $wallets = Ewallet::all();
        $count   = 0;

        foreach ($wallets as $wallet) {
            $wallet->syncBalance();
            $count++;
        }

        $this->command->info("✓ Synced balance on {$count} ewallets");

        // Display final balances for verification
        foreach ($wallets->fresh() as $wallet) {
            $label = $wallet->owner_type === 'SYSTEM'
                ? 'System Wallet'
                : "Provider Wallet #{$wallet->owner_id}";
            $this->command->line("   {$label}: {$wallet->balance} SAR");
        }
    }
}
