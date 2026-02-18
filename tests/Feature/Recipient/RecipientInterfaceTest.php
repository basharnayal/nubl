<?php

namespace Tests\Feature\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipientInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected $recipient;
    protected $provider;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        // Users
        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->recipient->assignRole('recipient');

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => 'provider'
        ]);
        $this->provider->assignRole('provider');

        // Provider Profile
        \App\Models\ProviderProfile::create([
            'user_id' => $this->provider->id,
            'full_name_ar' => 'Prov Full Name AR',
            'full_name_en' => 'Prov Full Name EN',
            'phone_number' => '0501234567',
            'email' => 'prov@test.com',
            'business_name_ar' => 'Biz Name AR',
            'business_name_en' => 'Test Provider Business',
            'unified_number' => '920000000',
            'business_category' => ['Food'],
            'address_ar' => 'Addr AR',
            'address_en' => 'Addr EN',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => 'Downtown'
        ]);

        // Menu Item
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Burger',
            'price' => 50.00,
            'is_active' => true,
        ]);

        // Create a V2 Request
        $this->request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'PENDING',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 50.00,
        ]);
    }

    /** @test */
    public function recipient_can_view_requests_index()
    {
        $response = $this->actingAs($this->recipient)
            ->get(route('recipient.requests.index'));

        $response->assertStatus(200);
        $response->assertSee('My Requests');
        $response->assertSee($this->provider->name); // User Name is shown in index
        $response->assertSee('50.00');
        $response->assertSee('SAR');
    }

    /** @test */
    public function recipient_can_view_request_details()
    {
        $response = $this->actingAs($this->recipient)
            ->get(route('recipient.requests.show', $this->request->id));

        $response->assertStatus(200);
        $response->assertSee('Request Details');
        $response->assertSee('Burger'); // Item Name
        $response->assertSee('50.00 SAR');
    }

    /** @test */
    public function recipient_can_view_providers_list()
    {
        $response = $this->actingAs($this->recipient)
            ->get(route('recipient.providers.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Provider Business');
    }
}
