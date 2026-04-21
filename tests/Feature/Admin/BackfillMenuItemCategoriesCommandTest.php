<?php

namespace Tests\Feature\Admin;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillMenuItemCategoriesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_maps_legacy_category_to_existing_category_for_provider_business_type(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'صاحب المطعم',
            'full_name_en' => 'Restaurant Owner',
            'phone_number' => '966500000123',
            'email' => 'provider@example.test',
            'business_name_ar' => 'مطعم تجريبي',
            'business_name_en' => 'Demo Restaurant',
            'unified_number' => '7000000000',
            'business_category' => ['Restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
        ]);

        $category = MenuItemCategory::create([
            'business_category' => 'Restaurant',
            'name' => 'Burgers',
            'slug' => 'burgers',
            'is_active' => true,
        ]);

        $item = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Classic Burger',
            'price' => 22.00,
            'category' => 'Burgers',
            'category_id' => null,
            'is_active' => true,
        ]);

        $this->artisan('backfill:menu-item-categories')
            ->assertSuccessful();

        $this->assertSame($category->id, $item->fresh()->category_id);
    }

    #[Test]
    public function command_creates_other_category_when_match_is_missing_or_business_category_is_invalid(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966500000124',
            'email' => 'provider2@example.test',
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Store',
            'unified_number' => '7000000001',
            'business_category' => ['InvalidCategory'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Jeddah',
            'region' => 'Makkah',
        ]);

        $item = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Mystery Item',
            'price' => 15.00,
            'category' => 'Does Not Exist',
            'category_id' => null,
            'is_active' => true,
        ]);

        $this->artisan('backfill:menu-item-categories')
            ->assertSuccessful();

        $fallback = MenuItemCategory::query()
            ->where('business_category', 'Other')
            ->where('slug', 'other')
            ->first();

        $this->assertNotNull($fallback);
        $this->assertSame('Other', $fallback->name);
        $this->assertTrue($fallback->is_active);
        $this->assertSame($fallback->id, $item->fresh()->category_id);
    }

    #[Test]
    public function command_does_not_touch_items_that_already_have_category_id(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966500000125',
            'email' => 'provider3@example.test',
            'business_name_ar' => 'مخبز',
            'business_name_en' => 'Bakery',
            'unified_number' => '7000000002',
            'business_category' => ['Bakery'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Dammam',
            'region' => 'Eastern',
        ]);

        $existingCategory = MenuItemCategory::create([
            'business_category' => 'Bakery',
            'name' => 'Desserts',
            'slug' => 'desserts',
            'is_active' => true,
        ]);

        $item = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Cake Slice',
            'price' => 9.50,
            'category' => 'Old Value',
            'category_id' => $existingCategory->id,
            'is_active' => true,
        ]);

        $this->artisan('backfill:menu-item-categories')
            ->assertSuccessful();

        $this->assertSame($existingCategory->id, $item->fresh()->category_id);
    }
}

