<?php

namespace Tests\Feature\Provider;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private MenuItemCategory $restaurantCategory;

    private MenuItemCategory $otherCategory;

    private MenuItemCategory $excludedCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $this->provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $this->provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966501234567',
            'email' => $this->provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Store',
            'unified_number' => '7000123456',
            'business_category' => ['restaurant'],
            'address_ar' => 'الرياض',
            'address_en' => 'Riyadh',
            'city' => 'medina',
            'region' => 'western',
            'location' => null,
        ]);

        $this->restaurantCategory = MenuItemCategory::create([
            'business_category' => 'restaurant',
            'name' => 'Main Meals',
            'slug' => 'main-meals',
            'is_active' => true,
        ]);
        $this->otherCategory = MenuItemCategory::create([
            'business_category' => 'Other',
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);
        $this->excludedCategory = MenuItemCategory::create([
            'business_category' => 'grocery',
            'name' => 'Groceries',
            'slug' => 'groceries',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function index_applies_search_and_category_filters_and_scopes_to_owner_items(): void
    {
        $target = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Burger Deluxe',
            'description' => 'beef',
            'price' => 25.00,
            'category' => 'Main Meals',
            'category_id' => $this->restaurantCategory->id,
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Tea',
            'description' => 'drink',
            'price' => 5.00,
            'category' => 'General',
            'category_id' => $this->otherCategory->id,
            'is_active' => true,
        ]);
        $otherProvider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $otherProvider->assignRole('provider');
        ProviderMenuItem::create([
            'provider_id' => $otherProvider->id,
            'name' => 'Burger Deluxe',
            'description' => 'other',
            'price' => 99.00,
            'category' => 'Main Meals',
            'category_id' => $this->restaurantCategory->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->provider)
            ->get(route('provider.menu-items.index', [
                'search' => 'Burger',
                'category_id' => $this->restaurantCategory->id,
            ]));

        $response->assertOk();
        $response->assertViewIs('provider.menu-items.index');
        $response->assertSee($target->name, false);
        $response->assertDontSee('Tea', false);
        $response->assertViewHas('categories', function ($categories): bool {
            $names = $categories->pluck('name')->all();

            return in_array('Main Meals', $names, true)
                && in_array('General', $names, true)
                && ! in_array('Groceries', $names, true);
        });
    }

    #[Test]
    public function index_can_filter_by_legacy_category_name_when_category_id_is_not_provided(): void
    {
        ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Legacy Soup',
            'description' => 'old',
            'price' => 8.00,
            'category' => 'LegacyCategory',
            'category_id' => null,
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Another Item',
            'description' => 'old',
            'price' => 11.00,
            'category' => 'OtherCategory',
            'category_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->provider)
            ->get(route('provider.menu-items.index', ['category' => 'LegacyCategory']));

        $response->assertOk();
        $response->assertSee('Legacy Soup', false);
        $response->assertDontSee('Another Item', false);
    }

    #[Test]
    public function create_page_loads_and_exposes_filtered_categories(): void
    {
        $response = $this->actingAs($this->provider)->get(route('provider.menu-items.create'));

        $response->assertOk();
        $response->assertViewIs('provider.menu-items.create');
        $response->assertViewHas('categories', function ($categories): bool {
            $names = $categories->pluck('name')->all();

            return in_array('Main Meals', $names, true)
                && in_array('General', $names, true)
                && ! in_array('Groceries', $names, true);
        });
    }

    #[Test]
    public function blocked_item_cannot_be_edited_updated_or_deactivated(): void
    {
        $blocked = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Blocked Item',
            'description' => 'blocked',
            'price' => 13.00,
            'category' => 'Main Meals',
            'category_id' => $this->restaurantCategory->id,
            'is_active' => true,
            'is_admin_blocked' => true,
        ]);

        $this->actingAs($this->provider)
            ->get(route('provider.menu-items.edit', $blocked->id))
            ->assertRedirect(route('provider.menu-items.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->provider)
            ->put(route('provider.menu-items.update', $blocked->id), [
                'name' => 'Attempt Update',
                'category_id' => $this->restaurantCategory->id,
                'price' => 99.99,
                'description' => 'desc',
                'is_active' => true,
            ])
            ->assertRedirect(route('provider.menu-items.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->provider)
            ->delete(route('provider.menu-items.destroy', $blocked->id))
            ->assertRedirect(route('provider.menu-items.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('provider_menu_items', [
            'id' => $blocked->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function update_replaces_existing_public_image_for_menu_item(): void
    {
        $item = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Image Item',
            'description' => 'old',
            'price' => 22.00,
            'category' => 'Main Meals',
            'category_id' => $this->restaurantCategory->id,
            'image_path' => 'menu-items/old.jpg',
            'is_active' => true,
            'is_admin_blocked' => false,
        ]);
        Storage::disk('public')->put('menu-items/old.jpg', 'old-image');

        $response = $this->actingAs($this->provider)
            ->put(route('provider.menu-items.update', $item->id), [
                'name' => 'Image Item Updated',
                'category_id' => $this->restaurantCategory->id,
                'price' => 23.00,
                'description' => 'new',
                'is_active' => true,
                'image' => UploadedFile::fake()->image('new-item.jpg'),
            ]);

        $response->assertRedirect(route('provider.menu-items.index'));

        $item->refresh();
        $this->assertSame('Image Item Updated', $item->name);
        $this->assertNotNull($item->image_path);
        $this->assertNotSame('menu-items/old.jpg', $item->image_path);
        Storage::disk('public')->assertMissing('menu-items/old.jpg');
        Storage::disk('public')->assertExists($item->image_path);
    }

    #[Test]
    public function edit_page_loads_for_unblocked_item_and_handles_string_business_category_profile(): void
    {
        DB::table('provider_profiles')
            ->where('user_id', $this->provider->id)
            ->update(['business_category' => '"restaurant"']);

        $item = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Editable Item',
            'description' => 'desc',
            'price' => 15.00,
            'category' => 'Main Meals',
            'category_id' => $this->restaurantCategory->id,
            'is_active' => true,
            'is_admin_blocked' => false,
        ]);

        $response = $this->actingAs($this->provider)->get(route('provider.menu-items.edit', $item->id));

        $response->assertOk();
        $response->assertViewIs('provider.menu-items.edit');
        $response->assertViewHas('menuItem', fn (ProviderMenuItem $menuItem): bool => $menuItem->is($item));
        $response->assertViewHas('categories', function ($categories): bool {
            $names = $categories->pluck('name')->all();

            return in_array('Main Meals', $names, true)
                && in_array('General', $names, true)
                && ! in_array('Groceries', $names, true);
        });
    }

    #[Test]
    public function destroy_deactivates_unblocked_item_and_logs_audit_activity(): void
    {
        $item = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'To Deactivate',
            'description' => 'desc',
            'price' => 10.00,
            'category' => 'Main Meals',
            'category_id' => $this->restaurantCategory->id,
            'is_active' => true,
            'is_admin_blocked' => false,
        ]);

        $this->actingAs($this->provider)
            ->delete(route('provider.menu-items.destroy', $item->id))
            ->assertRedirect(route('provider.menu-items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('provider_menu_items', [
            'id' => $item->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'menu_item.deactivated',
            'causer_id' => $this->provider->id,
        ]);
    }
}
