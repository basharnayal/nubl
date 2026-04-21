<?php

namespace Tests\Unit\Models;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderMenuItemScopesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owned_by_limits_to_provider_user_id(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        ProviderMenuItem::create([
            'provider_id' => $a->id,
            'name' => 'A1',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $b->id,
            'name' => 'B1',
            'price' => 2,
            'category' => 'x',
            'is_active' => true,
        ]);

        $this->assertSame(1, ProviderMenuItem::ownedBy($a->id)->count());
        $this->assertTrue(ProviderMenuItem::ownedBy($a->id)->where('name', 'A1')->exists());
    }

    #[Test]
    public function active_scope_excludes_inactive_rows(): void
    {
        $provider = User::factory()->create();

        ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'On',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Off',
            'price' => 2,
            'category' => 'x',
            'is_active' => false,
        ]);

        $this->assertSame(1, ProviderMenuItem::active()->where('provider_id', $provider->id)->count());
        $this->assertTrue(ProviderMenuItem::active()->where('name', 'On')->exists());
    }

    #[Test]
    public function image_url_accessor_encodes_paths_and_returns_null_for_empty_values(): void
    {
        $provider = User::factory()->create();

        $itemWithoutImage = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'No Image',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
            'image_path' => null,
        ]);
        $this->assertNull($itemWithoutImage->image_url);

        $itemWithImage = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'With Image',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
            'image_path' => 'menu-items/special item.jpg',
        ]);

        $this->assertStringContainsString(
            'storage/menu-items/special%20item.jpg',
            (string) $itemWithImage->image_url
        );
    }

    #[Test]
    public function menu_item_category_relationship_resolves_category_record(): void
    {
        $provider = User::factory()->create();
        $category = MenuItemCategory::create([
            'business_category' => 'Other',
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);

        $item = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Categorized',
            'price' => 5,
            'category' => 'General',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->assertTrue($item->menuItemCategory->is($category));
    }
}
