<?php

namespace Database\Seeders;

use App\Models\ProviderMenuItem;
use App\Models\Request;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds REDEEMABLE and FULFILLED requests for the recipient to test weekly allowance logic.
 * Run after RecipientSeeder, ProviderSeeder, and ProviderMenuItemSeeder.
 *
 * Creates requests totaling 120 SAR so remaining limit = 400 - 120 = 280.
 */
class AllowanceTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $recipient = User::where('email', 'recipient@nubl.com')->first();
        $provider = User::where('email', 'provider@nubl.com')->first();

        if (!$recipient || !$provider) {
            $this->command->warn('Recipient or provider not found. Run RecipientSeeder and ProviderSeeder first.');
            return;
        }

        $menuItems = ProviderMenuItem::where('provider_id', $provider->id)->get();
        if ($menuItems->isEmpty()) {
            $this->command->warn('No menu items found. Run ProviderMenuItemSeeder first.');
            return;
        }

        // Request 1: REDEEMABLE - Family meal package (85 SAR)
        $item1 = $menuItems->firstWhere('name', 'Family meal package') ?? $menuItems->first();
        $req1 = Request::firstOrCreate(
            [
                'recipient_id' => $recipient->id,
                'provider_id' => $provider->id,
                'status' => 'REDEEMABLE',
            ],
            [
                'reserved_amount' => $item1->price * 1,
                'funding_source' => 'CITY_FUND',
                'is_flagged' => false,
            ]
        );
        RequestItem::firstOrCreate(
            [
                'request_id' => $req1->id,
                'menu_item_id' => $item1->id,
            ],
            [
                'quantity' => 1,
                'price_snapshot' => $item1->price,
            ]
        );

        // Request 2: FULFILLED - Rice & Chicken Combo (35 SAR)
        $item2 = $menuItems->firstWhere('name', 'Rice & Chicken Combo') ?? $menuItems->skip(1)->first() ?? $menuItems->first();
        $req2 = Request::firstOrCreate(
            [
                'recipient_id' => $recipient->id,
                'provider_id' => $provider->id,
                'status' => 'FULFILLED',
            ],
            [
                'reserved_amount' => $item2->price * 1,
                'funding_source' => 'CITY_FUND',
                'is_flagged' => false,
            ]
        );
        RequestItem::firstOrCreate(
            [
                'request_id' => $req2->id,
                'menu_item_id' => $item2->id,
            ],
            [
                'quantity' => 1,
                'price_snapshot' => $item2->price,
            ]
        );

        $this->command->info('Allowance test data seeded.');
    }
}
