<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Comprehensive demo data seeder.
 *
 * Run AFTER DatabaseSeeder (base data must exist):
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class,
            DemoUsersSeeder::class,
            DemoPaymentSeeder::class,
            DemoRequestSeeder::class,
            DemoRedemptionSeeder::class,
            DemoProviderPayoutSeeder::class,
            DemoMiscSeeder::class,
            WalletBalanceSyncSeeder::class,
        ]);
    }
}
