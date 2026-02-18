<?php

namespace Tests\Feature\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\ProviderOperatingInfo;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipientRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected $recipient;
    protected $provider;
    protected $menuItem1;
    protected $menuItem2;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Setup Roles
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        // Create Recipient
        $this->recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->recipient->assignRole('recipient');

        // Create Provider
        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => 'provider',
        ]);
        $this->provider->assignRole('provider');

        // Create Provider Operating Info
        ProviderOperatingInfo::create([
            'user_id' => $this->provider->id,
            'daily_capacity' => 50,
            'operating_hours' => [],
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 mins',
        ]);

        // Create Menu Items
        $this->menuItem1 = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Burger',
            'description' => 'Delicious',
            'price' => 50.00,
            'category' => 'Meals',
            'is_active' => true,
        ]);

        $this->menuItem2 = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Fries',
            'description' => 'Crispy',
            'price' => 20.00,
            'category' => 'Sides',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function recipient_can_submit_multi_item_request_successfully()
    {
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2], // 100
                    ['id' => $this->menuItem2->id, 'quantity' => 1], // 20
                ]
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Check Header
        $this->assertDatabaseHas('requests', [
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 120.00, // (50*2) + (20*1)
            'status' => 'PENDING',
        ]);

        // Check Items
        $request = RequestModel::where('recipient_id', $this->recipient->id)->first();
        $this->assertCount(2, $request->items);

        $this->assertDatabaseHas('request_items', [
            'request_id' => $request->id,
            'menu_item_id' => $this->menuItem1->id,
            'quantity' => 2,
            'price_snapshot' => 50.00,
        ]);

        $this->assertDatabaseHas('request_items', [
            'request_id' => $request->id,
            'menu_item_id' => $this->menuItem2->id,
            'quantity' => 1,
            'price_snapshot' => 20.00,
        ]);
    }

    /** @test */
    public function weekly_allowance_exceeded_blocks_creation()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00')); // Wednesday

        // Existing request: 300 SAR
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 300.00,
            'status' => 'PENDING', // Counts towards allowance
            'funding_source' => 'CITY_FUND',
        ]);

        // Try to add 120 SAR (300 + 120 = 420 > 400)
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2], // 100
                    ['id' => $this->menuItem2->id, 'quantity' => 1], // 20
                ]
            ]);

        $response->assertSessionHasErrors(['allowance']);
        $this->assertDatabaseCount('requests', 1); // Only the setup one
    }

    /** @test */
    public function rejected_request_does_not_count_towards_allowance()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        // High value rejected request
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 300.00,
            'status' => 'ADMIN_REJECTED', // Should NOT count
            'funding_source' => 'CITY_FUND',
        ]);

        // Try to add 150 SAR (0 + 150 < 400)
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 3], // 150
                ]
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 2);
    }

    /** @test */
    public function adopted_request_does_not_count_towards_allowance()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        // Adopted request (Provider pays)
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 350.00,
            'status' => 'ADOPTED', // Should NOT count
            'funding_source' => 'PROVIDER_ADOPTION', // Explicitly logic checks source too
        ]);

        // Try to add 100 SAR (0 + 100 < 400)
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2], // 100
                ]
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 2);
    }

    /** @test */
    public function cannot_request_menu_item_not_belonging_to_provider()
    {
        $otherProvider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $otherProvider->assignRole('provider');

        // Setup other provider capacity
        ProviderOperatingInfo::create([
            'user_id' => $otherProvider->id,
            'daily_capacity' => 10,
            'operating_hours' => [],
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30',
        ]);

        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $otherProvider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 1], // Belongs to Provider A
                ]
            ]);

        $response->assertSessionHasErrors(['items.0.id']);
        $this->assertDatabaseCount('requests', 0);
    }
}
