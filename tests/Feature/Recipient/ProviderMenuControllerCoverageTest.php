<?php

namespace Tests\Feature\Recipient;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderMenuControllerCoverageTest extends TestCase
{
    use RefreshDatabase;

    private User $recipient;

    private User $openProvider;

    private User $closedProvider;

    private MenuItemCategory $restaurantCategory;

    private MenuItemCategory $otherCategory;

    private MenuItemCategory $groceryCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $this->recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $this->recipient->assignRole('recipient');

        $this->openProvider = $this->createProvider(
            name: 'Target Provider',
            businessName: 'Target Kitchen',
            businessCategory: ['restaurant'],
            acceptingOrders: true
        );

        $this->closedProvider = $this->createProvider(
            name: 'Closed Provider',
            businessName: 'Closed Kitchen',
            businessCategory: ['restaurant'],
            acceptingOrders: false
        );

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
        $this->groceryCategory = MenuItemCategory::create([
            'business_category' => 'grocery',
            'name' => 'Groceries',
            'slug' => 'groceries',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function index_filters_open_providers_by_search_term(): void
    {
        $otherOpenProvider = $this->createProvider(
            name: 'Another Provider',
            businessName: 'Different Store',
            businessCategory: ['restaurant'],
            acceptingOrders: true
        );

        $response = $this->actingAs($this->recipient)->get(route('recipient.providers.index', [
            'search' => 'Target',
        ]));

        $response->assertOk();
        $response->assertViewIs('recipient.providers.index');
        $response->assertViewHas('providers', function ($providers) use ($otherOpenProvider): bool {
            $ids = collect($providers->items())->pluck('id')->all();

            return in_array($this->openProvider->id, $ids, true)
                && ! in_array($otherOpenProvider->id, $ids, true)
                && ! in_array($this->closedProvider->id, $ids, true);
        });
    }

    #[Test]
    public function show_returns_not_found_for_non_provider_or_closed_provider(): void
    {
        $nonProvider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_DONOR,
        ]);
        $nonProvider->assignRole('donor');

        $this->actingAs($this->recipient)
            ->get(route('recipient.providers.show', $nonProvider))
            ->assertNotFound();

        $this->actingAs($this->recipient)
            ->get(route('recipient.providers.show', $this->closedProvider))
            ->assertNotFound();
    }

    #[Test]
    public function show_filters_items_by_category_id_and_search_and_returns_business_categories(): void
    {
        ProviderMenuItem::create([
            'provider_id' => $this->openProvider->id,
            'name' => 'Burger Classic',
            'description' => 'beef',
            'price' => 20.00,
            'category_id' => $this->restaurantCategory->id,
            'category' => 'Main Meals',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $this->openProvider->id,
            'name' => 'Legacy Burger',
            'description' => 'legacy',
            'price' => 21.00,
            'category_id' => null,
            'category' => 'Main Meals',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $this->openProvider->id,
            'name' => 'Tea',
            'description' => 'drink',
            'price' => 5.00,
            'category_id' => $this->otherCategory->id,
            'category' => 'General',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $this->openProvider->id,
            'name' => 'Inactive Burger',
            'description' => 'inactive',
            'price' => 1.00,
            'category_id' => $this->restaurantCategory->id,
            'category' => 'Main Meals',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->recipient)->get(route('recipient.providers.show', [
            'provider' => $this->openProvider->id,
            'category_id' => $this->restaurantCategory->id,
            'search' => 'Burger',
        ]));

        $response->assertOk();
        $response->assertViewIs('recipient.providers.show');
        $response->assertViewHas('menuItems', function ($items): bool {
            $names = $items->pluck('name')->all();

            return in_array('Burger Classic', $names, true)
                && in_array('Legacy Burger', $names, true)
                && ! in_array('Tea', $names, true)
                && ! in_array('Inactive Burger', $names, true);
        });
        $response->assertViewHas('categories', function ($categories): bool {
            $names = $categories->pluck('name')->all();

            return in_array('Main Meals', $names, true)
                && in_array('General', $names, true)
                && ! in_array('Groceries', $names, true);
        });
    }

    #[Test]
    public function show_supports_legacy_category_filter_parameter(): void
    {
        ProviderMenuItem::create([
            'provider_id' => $this->openProvider->id,
            'name' => 'Legacy Soup',
            'description' => 'legacy',
            'price' => 8.00,
            'category_id' => null,
            'category' => 'LegacyCategory',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $this->openProvider->id,
            'name' => 'Another Item',
            'description' => 'other',
            'price' => 9.00,
            'category_id' => null,
            'category' => 'OtherCategory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->recipient)->get(route('recipient.providers.show', [
            'provider' => $this->openProvider->id,
            'category' => 'LegacyCategory',
        ]));

        $response->assertOk();
        $response->assertViewHas('menuItems', function ($items): bool {
            $names = $items->pluck('name')->all();

            return in_array('Legacy Soup', $names, true)
                && ! in_array('Another Item', $names, true);
        });
    }

    private function createProvider(
        string $name,
        string $businessName,
        array $businessCategory,
        bool $acceptingOrders
    ): User {
        $phone = '9665'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        $provider = User::factory()->create([
            'name' => $name,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => $acceptingOrders,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'phone_number' => $phone,
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => $name,
            'phone_number' => $phone,
            'email' => $provider->email,
            'business_name_ar' => $businessName,
            'business_name_en' => $businessName,
            'unified_number' => '7000000001',
            'business_category' => $businessCategory,
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'location' => null,
        ]);

        return $provider;
    }
}
