<?php

namespace Database\Seeders;

use App\Http\Services\SystemWalletService;
use App\Models\FundTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CityFundDonationSeeder extends Seeder
{
    /**
     * Seed the city fund with 10000 from a donor (creates donor + fund_transaction).
     */
    public function run(): void
    {
        // Only add if we don't already have a donation in fund_transactions (idempotent)
        if (FundTransaction::where('source', FundTransaction::SOURCE_DONATION)->exists()) {
            return;
        }

        $donor = User::firstOrCreate(
            ['email' => 'seed-donor@nubl.com'],
            [
                'name' => 'Seed Donor',
                'password' => Hash::make('password'),
                'membership_type' => User::MEMBERSHIP_DONOR,
                'status' => User::STATUS_ACTIVE,
                'phone_number' => null,
                'is_active' => true,
            ]
        );

        if (! $donor->hasRole('donor')) {
            $donor->assignRole('donor');
        }

        $service = app(SystemWalletService::class);
        $service->addFundsFromDonation(10000, (int) $donor->id, null);
    }
}
