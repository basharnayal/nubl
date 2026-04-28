<?php

namespace Tests\Feature\Admin;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Notifications\AdminToggleMenuItemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMenuCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $provider;

    protected $menuItem;

    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create();
        $this->provider->assignRole('provider');

        $this->category = MenuItemCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'business_category' => 'Other',
            'is_active' => true,
        ]);

        $this->menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Item',
            'price' => 10.00,
            'category' => 'Test Category',
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_provider_listing()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.menus.index'));
        $response->assertStatus(200);
        $response->assertSee($this->provider->name);
    }

    public function test_admin_can_view_provider_items()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.menus.show', $this->provider));
        $response->assertStatus(200);
        $response->assertSee($this->menuItem->name);
    }

    public function test_admin_can_toggle_block_status()
    {
        Notification::fake();

        $this->assertFalse((bool) $this->menuItem->is_admin_blocked);

        $response = $this->actingAs($this->admin)->post(route('admin.menus.toggle-block', $this->menuItem));

        $response->assertRedirect();
        $this->assertTrue((bool) $this->menuItem->fresh()->is_admin_blocked);

        Notification::assertSentTo($this->provider, AdminToggleMenuItemNotification::class, function ($notification) {
            return $notification->isBlocked === true;
        });

        // Toggle back
        $this->actingAs($this->admin)->post(route('admin.menus.toggle-block', $this->menuItem));
        $this->assertFalse((bool) $this->menuItem->fresh()->is_admin_blocked);
    }

    public function test_blocked_item_is_not_visible_to_active_scope()
    {
        $this->assertCount(1, ProviderMenuItem::active()->get());

        $this->menuItem->update(['is_admin_blocked' => true]);

        $this->assertCount(0, ProviderMenuItem::active()->get());
    }

    public function test_provider_cannot_edit_blocked_item()
    {
        $this->menuItem->update(['is_admin_blocked' => true]);

        // Attempt to view edit page
        $response = $this->actingAs($this->provider)->get(route('provider.menu-items.edit', $this->menuItem));
        $response->assertRedirect(route('provider.menu-items.index'));
        $response->assertSessionHas('error');

        // Attempt to update
        $response = $this->actingAs($this->provider)->put(route('provider.menu-items.update', $this->menuItem), [
            'name' => 'Updated Name',
            'price' => 20.00,
            'category_id' => $this->category->id,
        ]);
        $response->assertRedirect(route('provider.menu-items.index'));
        $response->assertSessionHas('error');
        $this->assertEquals('Test Item', $this->menuItem->fresh()->name);

        // Attempt to deactivate
        $response = $this->actingAs($this->provider)->delete(route('provider.menu-items.destroy', $this->menuItem));
        $response->assertRedirect(route('provider.menu-items.index'));
        $response->assertSessionHas('error');
        $this->assertTrue((bool) $this->menuItem->fresh()->is_active);
    }

    public function test_admin_can_filter_providers_by_status()
    {
        $inactiveProvider = User::factory()->create(['is_active' => false]);
        $inactiveProvider->assignRole('provider');

        $response = $this->actingAs($this->admin)->get(route('admin.menus.index', ['status' => 'active']));
        $response->assertSee($this->provider->name);
        $response->assertDontSee($inactiveProvider->name);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.index', ['status' => 'inactive']));
        $response->assertDontSee($this->provider->name);
        $response->assertSee($inactiveProvider->name);
    }

    public function test_admin_can_filter_providers_by_search_and_business_category()
    {
        $matchedProvider = User::factory()->create(['name' => 'Filtered Bakery']);
        $matchedProvider->assignRole('provider');
        $this->createProviderProfile($matchedProvider, ['bakery'], 'Filtered Bakery');

        $unmatchedProvider = User::factory()->create(['name' => 'Filtered Grocery']);
        $unmatchedProvider->assignRole('provider');
        $this->createProviderProfile($unmatchedProvider, ['grocery'], 'Filtered Grocery');

        $response = $this->actingAs($this->admin)->get(route('admin.menus.index', [
            'search' => 'Bakery',
            'category' => 'bakery',
        ]));

        $response->assertOk();
        $response->assertSee($matchedProvider->name);
        $response->assertDontSee($unmatchedProvider->name);
    }

    public function test_admin_can_filter_items_by_status()
    {
        $blockedItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Blocked Item',
            'price' => 15.00,
            'category' => 'Test Category',
            'category_id' => $this->category->id,
            'is_active' => true,
            'is_admin_blocked' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.show', [$this->provider, 'status' => 'active']));
        $response->assertSee($this->menuItem->name);
        $response->assertDontSee($blockedItem->name);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.show', [$this->provider, 'status' => 'blocked']));
        $response->assertDontSee($this->menuItem->name);
        $response->assertSee($blockedItem->name);

        $inactiveItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Inactive Item',
            'price' => 12.00,
            'category' => 'Test Category',
            'category_id' => $this->category->id,
            'is_active' => false,
            'is_admin_blocked' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.show', [$this->provider, 'status' => 'inactive']));
        $response->assertDontSee($this->menuItem->name);
        $response->assertDontSee($blockedItem->name);
        $response->assertSee($inactiveItem->name);
    }

    public function test_admin_can_filter_items_by_category()
    {
        $otherCategory = MenuItemCategory::create([
            'name' => 'Other Category',
            'slug' => 'other-category',
            'business_category' => 'Other',
            'is_active' => true,
        ]);

        $otherItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Other Item',
            'price' => 5.00,
            'category' => 'Other Category',
            'category_id' => $otherCategory->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.show', [$this->provider, 'category_id' => $this->category->id]));
        $response->assertSee($this->menuItem->name);
        $response->assertDontSee($otherItem->name);
    }

    public function test_admin_show_rejects_non_provider_users()
    {
        $donor = User::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.menus.show', $donor))
            ->assertNotFound();
    }

    public function test_admin_can_search_provider_items_and_filter_categories_by_primary_business_category()
    {
        $this->createProviderProfile($this->provider, ['restaurant'], 'Provider Restaurant');

        $restaurantCategory = MenuItemCategory::create([
            'name' => 'Restaurant Category',
            'slug' => 'restaurant-category',
            'business_category' => 'restaurant',
            'is_active' => true,
        ]);
        MenuItemCategory::create([
            'name' => 'Hidden Grocery Category',
            'slug' => 'hidden-grocery-category',
            'business_category' => 'grocery',
            'is_active' => true,
        ]);

        $searchedItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Searchable Soup',
            'price' => 18.00,
            'category' => 'Restaurant Category',
            'category_id' => $restaurantCategory->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.menus.show', [
            $this->provider,
            'search' => 'Soup',
        ]));

        $response->assertOk();
        $response->assertSee($searchedItem->name);
        $response->assertDontSee($this->menuItem->name);
        $response->assertSee('Restaurant Category');
        $response->assertSee('Test Category');
        $response->assertDontSee('Hidden Grocery Category');
    }

    private function createProviderProfile(User $provider, array $businessCategories, string $businessName): ProviderProfile
    {
        return ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => $provider->name,
            'phone_number' => '966500000000',
            'email' => $provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => $businessName,
            'unified_number' => '7000000000',
            'business_category' => $businessCategories,
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'Central',
        ]);
    }
}
