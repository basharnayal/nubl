<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    }

    #[Test]
    public function provider_can_open_operating_profile_edit_page(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => 'Provider',
            'phone_number' => '966501111111',
            'email' => $provider->email,
            'business_name_ar' => 'Shop',
            'business_name_en' => 'Shop',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'medina',
            'region' => 'western',
        ]);

        $weekdays = array_keys(config('provider.weekdays'));
        $operatingHours = [];
        foreach ($weekdays as $day) {
            $operatingHours[$day] = ['open' => '09:00', 'close' => '17:00', 'closed' => false];
        }

        ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => $operatingHours,
            'daily_capacity' => 50,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'pickup_notes' => null,
        ]);

        $response = $this->actingAs($provider)->get(route('provider.profile.edit'));

        $response->assertOk();
        $response->assertViewIs('provider.profile.edit');
        $response->assertViewHas('profile', fn (ProviderProfile $profile): bool => $profile->user_id === $provider->id);
        $response->assertViewHas('operatingInfo', fn (ProviderOperatingInfo $info): bool => $info->user_id === $provider->id);
        $response->assertViewHas('serviceTypes');
        $response->assertViewHas('weekdayKeys');
        $response->assertViewHas('adoptionSupportOptions');
    }

    #[Test]
    public function provider_can_update_operating_profile_and_pickup_notes(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966501111111',
            'email' => $provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Shop',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'medina',
            'region' => 'western',
        ]);

        $weekdays = array_keys(config('provider.weekdays'));
        $operatingHours = [];
        foreach ($weekdays as $day) {
            $operatingHours[$day] = ['open' => '09:00', 'close' => '17:00', 'closed' => false];
        }

        ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => $operatingHours,
            'daily_capacity' => 50,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'pickup_notes' => null,
        ]);

        $payload = [
            'daily_capacity' => 75,
            'service_type' => ['pickup', 'delivery'],
            'estimated_preparation_order_time' => '45 minutes',
            'adoption_support' => 'partially',
            'pickup_notes' => 'Ring the side door bell.',
        ];
        foreach ($weekdays as $day) {
            $payload['operating_hours'][$day] = ['closed' => true];
        }

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), $payload);

        $response->assertRedirect(route('provider.profile.edit'));
        $response->assertSessionHas('success');

        $provider->refresh();
        $info = $provider->providerOperatingInfo;
        $this->assertSame(75, (int) $info->daily_capacity);
        $this->assertSame(['pickup', 'delivery'], $info->service_type);
        $this->assertSame('Ring the side door bell.', $info->pickup_notes);
    }

    #[Test]
    public function provider_can_update_business_profile_from_account_profile_page(): void
    {
        $provider = User::factory()->create([
            'name' => 'Provider',
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'phone_number' => '966501111111',
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966501111111',
            'email' => $provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Shop',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'medina',
            'region' => 'western',
            'location' => null,
        ]);

        $response = $this->actingAs($provider)->patch(route('profile.provider-business.update'), [
            'full_name_ar' => 'مزود محدث',
            'full_name_en' => 'Updated Provider',
            'phone_number' => '501111111',
            'business_name_ar' => 'متجر جديد',
            'business_name_en' => 'New Shop',
            'business_category' => ['restaurant', 'bakery'],
            'address_ar' => 'عنوان جديد',
            'address_en' => 'New address line',
            'city' => 'medina',
            'region' => 'western',
            'location' => 'Near gate 1',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'business-profile-updated');

        $provider->refresh();
        $this->assertSame('Updated Provider', $provider->name);
        $this->assertSame('966501111111', $provider->phone_number);

        $profile = $provider->providerProfile;
        $this->assertSame('مزود محدث', $profile->full_name_ar);
        $this->assertSame('New Shop', $profile->business_name_en);
        $this->assertSame(['restaurant', 'bakery'], $profile->business_category);
        $this->assertSame('Near gate 1', $profile->location);
    }

    #[Test]
    public function non_provider_cannot_update_provider_business_profile(): void
    {
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $user->assignRole('recipient');

        $response = $this->actingAs($user)->patch(route('profile.provider-business.update'), [
            'full_name_ar' => 'x',
            'full_name_en' => 'x',
            'phone_number' => '501111111',
            'business_name_ar' => 'x',
            'business_name_en' => 'x',
            'business_category' => ['restaurant'],
            'address_ar' => 'x',
            'address_en' => 'x',
            'city' => 'medina',
            'region' => 'western',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function provider_can_update_financial_profile_from_account_profile_page(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966501111111',
            'email' => $provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Shop',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'medina',
            'region' => 'western',
        ]);

        ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Old Bank',
            'iban' => 'SA0000000000000000000000',
            'account_holder_name' => 'Old Holder',
        ]);

        $response = $this->actingAs($provider)->patch(route('profile.provider-financial.update'), [
            'bank_name' => 'Al Rajhi Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'New Holder Name',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'financial-profile-updated');

        $financial = $provider->fresh()->providerFinancialInfo;
        $this->assertSame('Al Rajhi Bank', $financial->bank_name);
        $this->assertSame('SA0380000000608010167519', $financial->iban);
        $this->assertSame('New Holder Name', $financial->account_holder_name);
    }

    #[Test]
    public function non_provider_cannot_update_provider_financial_profile(): void
    {
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $user->assignRole('recipient');

        $response = $this->actingAs($user)->patch(route('profile.provider-financial.update'), [
            'bank_name' => 'x',
            'iban' => 'SA123',
            'account_holder_name' => 'x',
        ]);

        $response->assertForbidden();
    }
}
