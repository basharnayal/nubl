<?php

namespace Tests\Feature\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderMenuV2Test extends TestCase
{
    use RefreshDatabase;

    protected $recipient;
    protected $provider;
    protected $menuItem;

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

        // Provider Profile (needed for index/search queries sometimes, but show uses User)
        \App\Models\ProviderProfile::create([
            'user_id' => $this->provider->id,
            'full_name_ar' => 'Provider Name AR',
            'full_name_en' => 'Provider Name EN',
            'phone_number' => '0500000000',
            'email' => 'provider@example.com',
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Test Provider',
            'unified_number' => '7000000000',
            'business_category' => ['Food'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => 'Downtown'
        ]);

        $this->menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Burger',
            'price' => 50.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function provider_menu_page_loads_with_correct_weekly_usage_v2()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00')); // Wednesday

        // Create a previous V2 request that consumes allowance
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 150.00,
            'status' => 'PENDING',
            'funding_source' => 'CITY_FUND',
        ]);

        // Create a rejected request (should not count)
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 500.00,
            'status' => 'ADMIN_REJECTED',
            'funding_source' => 'CITY_FUND',
        ]);

        // Create an adopted request (should not count)
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'ADOPTED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);

        $response = $this->actingAs($this->recipient)
            ->get(route('recipient.providers.show', $this->provider->id));

        $response->assertStatus(200);

        // Assert view variables
        $response->assertViewHas('weeklyUsed', 150.00);

        // Check for text on page (assuming valid blade)
        // Adjust this assertion based on your actual blade implementation if needed.
        // Usually something like "Used: 150 SAR" or similar.
    }
}
