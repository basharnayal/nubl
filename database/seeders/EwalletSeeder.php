<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use Illuminate\Database\Seeder;

class EwalletSeeder extends Seeder
{
    public function run(): void
    {
        // Create a single SYSTEM wallet if none exists yet (balance calculated from fund_transactions)
        if (! Ewallet::where('owner_type', 'SYSTEM')->exists()) {
            $systemWallet = Ewallet::create([
                'owner_type' => 'SYSTEM',
                'owner_id' => null,
                'balance' => 0,
                'status' => true,
            ]);
            // Seed initial city fund for testing (balance = sum IN - sum OUT)
            FundTransaction::create([
                'wallet_id' => $systemWallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_DONATION,
                'amount' => 1000,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
            ]);
            $systemWallet->syncBalance();
        }
    }
}

