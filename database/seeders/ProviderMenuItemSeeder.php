<?php

namespace Database\Seeders;

use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProviderMenuItemSeeder extends Seeder
{
    /**
     * Seed provider menu items for seeded providers.
     */
    public function run(): void
    {
        $providerEmails = ['provider@nubl.com', 'community@nubl.com'];
        $providers = User::whereIn('email', $providerEmails)->where('membership_type', User::MEMBERSHIP_PROVIDER)->get();

        if ($providers->isEmpty()) {
            $this->command->warn('No providers found. Run ProviderSeeder first.');
            return;
        }

        $menuItems = [
            [
                'provider_email' => 'provider@nubl.com',
                'items' => [
                    ['name' => 'Family meal package', 'description' => 'Complete meal for 4-6 people', 'price' => 85.00, 'category' => 'meal', 'max_per_request' => 2],
                    ['name' => 'Daily support order', 'description' => 'Single daily meal support', 'price' => 25.00, 'category' => 'meal', 'max_per_request' => 5],
                    ['name' => 'Rice & Chicken Combo', 'description' => 'Traditional rice with grilled chicken', 'price' => 35.00, 'category' => 'meal', 'max_per_request' => 3],
                    ['name' => 'Vegetable Soup', 'description' => 'Fresh vegetable soup', 'price' => 15.00, 'category' => 'meal', 'max_per_request' => 4],
                    ['name' => 'Fresh Bread Basket', 'description' => 'Assorted fresh bread', 'price' => 12.00, 'category' => 'bakery', 'max_per_request' => 2],
                ],
            ],
            [
                'provider_email' => 'community@nubl.com',
                'items' => [
                    ['name' => 'Weekly assistance', 'description' => 'Weekly meal package', 'price' => 120.00, 'category' => 'meal', 'max_per_request' => 1],
                    ['name' => 'Lunch Box', 'description' => 'Single lunch box', 'price' => 20.00, 'category' => 'meal', 'max_per_request' => 5],
                    ['name' => 'Breakfast Pack', 'description' => 'Morning breakfast essentials', 'price' => 18.00, 'category' => 'meal', 'max_per_request' => 3],
                    ['name' => 'Pastry Set', 'description' => 'Assorted pastries', 'price' => 22.00, 'category' => 'bakery', 'max_per_request' => 2],
                ],
            ],
        ];

        foreach ($menuItems as $group) {
            $provider = User::where('email', $group['provider_email'])->first();
            if (!$provider) {
                continue;
            }

            foreach ($group['items'] as $item) {
                ProviderMenuItem::firstOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'name' => $item['name'],
                    ],
                    [
                        'description' => $item['description'],
                        'price' => $item['price'],
                        'category' => $item['category'],
                        'max_per_request' => $item['max_per_request'] ?? null,
                        'is_active' => true,
                        'image_path' => null,
                    ]
                );
            }
        }
    }
}
