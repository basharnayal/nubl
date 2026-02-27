<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackfillMenuItemCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:menu-item-categories';

    protected $description = 'Backfills provider_menu_items table to map legacy string categories to new category_id references';

    public function handle()
    {
        $this->info('Starting backfill for menu item categories...');

        $items = \App\Models\ProviderMenuItem::whereNull('category_id')->get();
        $this->info("Found {$items->count()} items to backfill.");

        foreach ($items as $item) {
            $provider = $item->provider;
            if (!$provider)
                continue;

            $profile = $provider->providerProfile;
            $businessCategories = $profile?->business_category ?? ['Other'];
            $businessCategory = 'Other';
            if (is_array($businessCategories) && count($businessCategories) > 0) {
                $businessCategory = $businessCategories[0];
            } else if (is_string($businessCategories)) {
                $businessCategory = $businessCategories;
            }

            $validMainCategories = ['Restaurant', 'Catering', 'Bakery', 'Grocery', 'Food truck', 'Other'];
            if (!in_array($businessCategory, $validMainCategories)) {
                $businessCategory = 'Other';
            }

            $legacyCategoryStr = $item->category;
            $slug = \Illuminate\Support\Str::slug($legacyCategoryStr ?: 'Other');

            $category = \App\Models\MenuItemCategory::where('business_category', $businessCategory)
                ->where('slug', $slug)
                ->first();

            // Fallback to "other" (case insensitive slug)
            if (!$category) {
                $category = \App\Models\MenuItemCategory::firstOrCreate([
                    'business_category' => $businessCategory,
                    'slug' => 'other',
                ], [
                    'name' => 'Other',
                    'is_active' => true,
                ]);
            }

            $item->update(['category_id' => $category->id]);
        }

        $this->info('Backfill completed.');
    }
}
