<?php

namespace App\Console\Commands;

use App\Models\Ewallet;
use Illuminate\Console\Command;

class SyncWalletBalances extends Command
{
    protected $signature = 'wallets:sync-balances';

    protected $description = 'Recalculate and sync ewallet balance column from fund_transactions';

    public function handle(): int
    {
        $wallets = Ewallet::all();
        $count = 0;

        foreach ($wallets as $wallet) {
            $before = (float) $wallet->getRawOriginal('balance');
            $wallet->syncBalance();
            $after = (float) $wallet->fresh()->getRawOriginal('balance');
            if ($before !== $after) {
                $count++;
                $this->line("Wallet {$wallet->id} ({$wallet->owner_type}): {$before} → {$after}");
            }
        }

        $this->info("Synced {$count} wallet(s).");

        return self::SUCCESS;
    }
}
