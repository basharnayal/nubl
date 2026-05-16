<?php

namespace Tests\Feature\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipientDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        app()->setLocale('en');
    }

    #[Test]
    public function recipient_dashboard_returns_expected_counts_limit_and_activity_chart_data(): void
    {
        config(['recipient.weekly_allowance_limit' => 500]);

        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $recipient->assignRole('recipient');

        $providerA = $this->createProvider('provider-a@example.test', 'Provider A');
        $providerB = $this->createProvider('provider-b@example.test', 'Provider B');

        $itemA = ProviderMenuItem::create([
            'provider_id' => $providerA->id,
            'name' => 'Meal A',
            'price' => 40.00,
            'is_active' => true,
        ]);
        $itemB = ProviderMenuItem::create([
            'provider_id' => $providerB->id,
            'name' => 'Meal B',
            'price' => 60.00,
            'is_active' => true,
        ]);

        $requested = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $providerA->id,
            'reserved_amount' => 40.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);
        $requested->items()->create([
            'menu_item_id' => $itemA->id,
            'quantity' => 1,
            'price_snapshot' => 40.00,
        ]);

        $approved = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $providerA->id,
            'reserved_amount' => 50.00,
            'status' => 'APPROVED',
            'funding_source' => 'CITY_FUND',
        ]);
        $approved->items()->create([
            'menu_item_id' => $itemA->id,
            'quantity' => 1,
            'price_snapshot' => 50.00,
        ]);

        $redeemable = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $providerB->id,
            'reserved_amount' => 60.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);
        $redeemable->items()->create([
            'menu_item_id' => $itemB->id,
            'quantity' => 1,
            'price_snapshot' => 60.00,
        ]);

        $fulfilled = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $providerB->id,
            'reserved_amount' => 100.00,
            'status' => 'FULFILLED',
            'funding_source' => 'CITY_FUND',
        ]);
        $fulfilled->items()->create([
            'menu_item_id' => $itemB->id,
            'quantity' => 1,
            'price_snapshot' => 100.00,
        ]);

        $otherRecipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        RequestModel::create([
            'recipient_id' => $otherRecipient->id,
            'provider_id' => $providerA->id,
            'reserved_amount' => 999.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        $response = $this->actingAs($recipient)->get(route('recipient.dashboard'));

        $response->assertOk();
        $response->assertViewIs('recipient.dashboard');
        $response->assertViewHas('activeRequestsCount', 3);
        $response->assertViewHas('pendingCount', 2);
        $response->assertViewHas('completedOrdersCount', 1);
        $response->assertViewHas('providersCount', 2);
        $response->assertViewHas('weeklyLimit', 500.0);
        $response->assertViewHas('remainingLimit', 300.0);

        $chart = $response->viewData('activityChartData');
        // Activity chart: one bucket per day for the last 7 days (today + 6 prior).
        $this->assertCount(7, $chart['categories']);
        $this->assertCount(7, $chart['series']);
        $this->assertSame(6, $chart['selectedIndex']);
    }

    #[Test]
    public function provider_menu_route_returns_404_for_non_provider_or_provider_without_profile(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $recipient->assignRole('recipient');

        $donor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $providerNoProfile = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $providerNoProfile->assignRole('provider');

        $this->actingAs($recipient)
            ->get(route('recipient.providers.menu', $donor))
            ->assertNotFound();

        $this->actingAs($recipient)
            ->get(route('recipient.providers.menu', $providerNoProfile))
            ->assertNotFound();
    }

    #[Test]
    public function provider_menu_route_returns_only_active_items_for_valid_provider(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $recipient->assignRole('recipient');

        $provider = $this->createProvider('menu-provider@example.test', 'Menu Provider');

        ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Active Meal',
            'description' => 'Visible item',
            'price' => 25.00,
            'category' => 'Main',
            'is_active' => true,
            'is_admin_blocked' => false,
        ]);

        ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Inactive Meal',
            'description' => 'Hidden item',
            'price' => 20.00,
            'category' => 'Main',
            'is_active' => false,
            'is_admin_blocked' => false,
        ]);

        $response = $this->actingAs($recipient)->get(route('recipient.providers.menu', $provider));

        $response->assertOk();
        $response->assertViewIs('recipient.provider-menu');
        $response->assertSee('Active Meal');
        $response->assertDontSee('Inactive Meal');
    }

    #[Test]
    public function chart_data_api_rejects_invalid_date_query(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $recipient->assignRole('recipient');

        $this->actingAs($recipient)
            ->get(route('recipient.chart-data.api', ['date' => 'abc']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    private function createProvider(string $email, string $businessName): User
    {
        $provider = User::factory()->create([
            'email' => $email,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider User',
            'phone_number' => '9665'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => $email,
            'business_name_ar' => 'اسم العمل',
            'business_name_en' => $businessName,
            'unified_number' => '7000000000',
            'business_category' => ['Restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => 'Downtown',
        ]);

        return $provider;
    }
}
