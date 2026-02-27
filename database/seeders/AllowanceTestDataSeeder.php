<?php

namespace Database\Seeders;

use App\Models\ProviderMenuItem;
use App\Models\Request;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds REDEEMABLE, FULFILLED, and APPROVED requests for the recipient to test weekly allowance logic.
 * Run after RecipientSeeder, ProviderSeeder, and ProviderMenuItemSeeder.
 *
 * Counts toward allowance: REDEEMABLE (85) + FULFILLED (35) + FULFILLED (15) = 135 SAR. remaining = 265.
 * APPROVED (provider adopted, 25 SAR) does NOT count toward allowance.
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

        // Request 3: FULFILLED - Vegetable Soup (15 SAR)
        $item3 = $menuItems->firstWhere('name', 'Vegetable Soup') ?? $menuItems->skip(2)->first() ?? $menuItems->first();
        $req3 = Request::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => $item3->price * 1,
            'funding_source' => 'CITY_FUND',
            'status' => 'FULFILLED',
            'is_flagged' => false,
        ]);
        $req3->items()->create([
            'menu_item_id' => $item3->id,
            'quantity' => 1,
            'price_snapshot' => $item3->price,
        ]);

        // Request 4: APPROVED - Provider adopted (Daily support order, 25 SAR) — does NOT count toward allowance
        $item4 = $menuItems->firstWhere('name', 'Daily support order') ?? $menuItems->first();
        $req4 = Request::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => $item4->price * 1,
            'funding_source' => 'PROVIDER_ADOPTION',
            'status' => 'APPROVED',
            'is_flagged' => false,
        ]);
        $req4->items()->create([
            'menu_item_id' => $item4->id,
            'quantity' => 1,
            'price_snapshot' => $item4->price,
        ]);

        $this->command->info('Allowance test data seeded.');
    }
}
