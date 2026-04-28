<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EwalletSeeder extends Seeder
{
    public function run(): void
    {
        if (! Ewallet::where('owner_type', 'SYSTEM')->exists()) {
            $systemWallet = Ewallet::create([
                'owner_type' => 'SYSTEM',
                'owner_id' => null,
                'balance' => 0,
                'status' => true,
            ]);

            // Use the seeded donor account as sponsor for the initial city-fund bootstrap.
            // (See DatabaseSeeder -> DonorSeeder.)
            $donor = User::query()
                ->where('email', 'donor-seed@nubl.com')
                ->firstOrFail();

            $payment = Payment::create([
                'sponsor_id' => $donor->id,
                'gateway' => Payment::GATEWAY_MYFATOORAH,
                'status' => Payment::STATUS_SUCCEEDED,
                'amount' => 1000,
            ]);

            FundTransaction::create([
                'wallet_id' => $systemWallet->id,
                'sponsor_id' => $donor->id,
                'source' => FundTransaction::SOURCE_DONATION,
                'amount' => 1000,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => $payment->id,
                'request_id' => null,
                'order_redemption_id' => null,
            ]);
            $systemWallet->syncBalance();
        }
    }
}
