<?php

namespace Tests\Feature;

use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderMenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_provider_can_create_menu_item()
    {
        $provider = User::factory()->create([
            'membership_type' => 'provider',
            'status' => 'active',
        ]);
        $provider->assignRole('provider');

        // Create profile manually with ALL required fields
        ProviderProfile::create([
            'user_id' => $provider->id,
            'business_name_en' => 'Test Burger Joint',
            'business_name_ar' => 'برجر تست',
            'full_name_en' => 'John Doe',
            'full_name_ar' => 'جون دو',
            'phone_number' => '0500000000',
            'email' => 'test@burger.com',
            'unified_number' => '7000000000',
            'business_category' => ['Food', 'Burger'],
            'address_en' => '123 Main St',
            'address_ar' => '123 شارع رئيسي',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => '24.7136, 46.6753',
        ]);

        $response = $this->actingAs($provider)->post(route('provider.menu-items.store'), [
            'name' => 'Cheeseburger',
            'description' => 'Delicious cheese burger',
            'price' => 25.50,
            'category' => 'Main',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('provider.menu-items.index'));
        $this->assertDatabaseHas('provider_menu_items', [
            'name' => 'Cheeseburger',
            'provider_id' => $provider->id,
            'price' => 25.50
        ]);
    }

    public function test_provider_can_edit_own_menu_item()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        $item = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Old Name',
            'price' => 10.00,
            'category' => 'Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($provider)->put(route('provider.menu-items.update', $item->id), [
            'name' => 'New Name',
            'price' => 15.00,
            'category' => 'Test',
            'description' => 'Updated desc',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('provider.menu-items.index'));
        $this->assertDatabaseHas('provider_menu_items', [
            'id' => $item->id,
            'name' => 'New Name',
            'price' => 15.00
        ]);
    }

    public function test_provider_cannot_edit_other_provider_item()
    {
        $providerA = User::factory()->create();
        $providerA->assignRole('provider');

        $providerB = User::factory()->create();
        $providerB->assignRole('provider');

        $itemB = ProviderMenuItem::create([
            'provider_id' => $providerB->id,
            'name' => 'Provider B Item',
            'price' => 10.00,
            'category' => 'Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($providerA)->put(route('provider.menu-items.update', $itemB->id), [
            'name' => 'Hacked Name',
            'price' => 0.00,
            'category' => 'Test',
            'is_active' => true,
        ]);

        // Expect 404 because we use findOrFail scopeOwnedBy
        $response->assertStatus(404);

        $this->assertDatabaseHas('provider_menu_items', [
            'id' => $itemB->id,
            'name' => 'Provider B Item', // Unchanged
        ]);
    }

    public function test_recipient_can_browse_providers()
    {
        $recipient = User::factory()->create();
        $recipient->assignRole('recipient');

        $provider = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $provider->assignRole('provider');
        ProviderProfile::create([
            'user_id' => $provider->id,
            'business_name_en' => 'Visible Provider',
            'business_name_ar' => 'مقدم خدمة مرئي',
            'full_name_en' => 'John Doe',
            'full_name_ar' => 'جون دو',
            'phone_number' => '0500000000',
            'email' => 'test@visible.com',
            'unified_number' => '7000000000',
            'business_category' => ['General'],
            'address_en' => '123 Main St',
            'address_ar' => '123 شارع رئيسي',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => '24.7136, 46.6753',
        ]);

        $response = $this->actingAs($recipient)->get(route('recipient.providers.index'));

        $response->assertStatus(200);
        $response->assertSee('Visible Provider');
    }

    public function test_recipient_can_view_provider_menu()
    {
        $recipient = User::factory()->create();
        $recipient->assignRole('recipient');

        $provider = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $provider->assignRole('provider');
        ProviderProfile::create([
            'user_id' => $provider->id,
            'business_name_en' => 'Menu Provider',
            'business_name_ar' => 'مقدم قائمة الطعام',
            'full_name_en' => 'Jane Doe',
            'full_name_ar' => 'جين دو',
            'phone_number' => '0500000001',
            'email' => 'test@menu.com',
            'unified_number' => '7000000001',
            'business_category' => ['Food'],
            'address_en' => '456 Side St',
            'address_ar' => '456 شارع جانبي',
            'city' => 'Jeddah',
            'region' => 'Makkah',
            'location' => '21.5433, 39.1728',
        ]);

        $activeItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Active Pizza',
            'price' => 30.00,
            'category' => 'Main',
            'is_active' => true,
        ]);

        $inactiveItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Inactive Soup',
            'price' => 10.00,
            'category' => 'Starters',
            'is_active' => false,
        ]);

        $response = $this->actingAs($recipient)->get(route('recipient.providers.show', $provider->id));

        $response->assertStatus(200);
        $response->assertSee('Active Pizza');
        $response->assertDontSee('Inactive Soup');
    }

    public function test_recipient_cannot_access_provider_routes()
    {
        $recipient = User::factory()->create();
        $recipient->assignRole('recipient');

        $response = $this->actingAs($recipient)->post(route('provider.menu-items.store'), [
            'name' => 'Hack Item',
        ]);

        $response->assertStatus(403);
    }

    public function test_provider_can_upload_image()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $provider = User::factory()->create(['membership_type' => 'provider', 'status' => 'active']);
        $provider->assignRole('provider');

        // Ensure profile exists for any middleware that might check it (though validation generally doesn't)
        ProviderProfile::create([
            'user_id' => $provider->id,
            'business_name_en' => 'Image Provider',
            'business_name_ar' => 'مقدم صور',
            'full_name_en' => 'John Doe',
            'full_name_ar' => 'جون دو',
            'phone_number' => '0500000000',
            'email' => 'test@image.com',
            'unified_number' => '7000000000',
            'business_category' => ['Food'],
            'address_en' => '123 Main St',
            'address_ar' => '123 شارع رئيسي',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => '24.7136, 46.6753',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('item.jpg', 100);

        $response = $this->actingAs($provider)->post(route('provider.menu-items.store'), [
            'name' => 'Burger with Image',
            'price' => 50.00,
            'category' => 'Main',
            'is_active' => true,
            'image' => $file,
        ]);

        $response->assertRedirect(route('provider.menu-items.index'));

        $this->assertDatabaseHas('provider_menu_items', [
            'name' => 'Burger with Image',
            'provider_id' => $provider->id,
        ]);

        $item = ProviderMenuItem::where('name', 'Burger with Image')->first();
        $this->assertNotNull($item->image_path, 'Image path is null');

        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($item->image_path));
    }
}
