<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    }

    private function seedProviderWithOperating(User $provider): void
    {
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
    }

    #[Test]
    public function toggle_store_logs_activity(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $provider->assignRole('provider');
        $this->seedProviderWithOperating($provider);

        $before = Activity::query()->count();

        $response = $this->actingAs($provider)->post(route('provider.profile.toggle-active'));
        $response->assertRedirect();

        $this->assertSame($before + 1, Activity::query()->count());
        $latest = Activity::query()->latest('id')->first();
        $this->assertSame('provider.store_availability_toggled', $latest->description);
        $props = $latest->properties?->toArray() ?? [];
        $this->assertSame('provider', $props['entity'] ?? null);
        $this->assertTrue($props['accepting_orders_before'] ?? null);
        $this->assertFalse($props['accepting_orders_after'] ?? null);
    }

    #[Test]
    public function operating_profile_update_logs_activity(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $provider->assignRole('provider');
        $this->seedProviderWithOperating($provider);

        $weekdays = array_keys(config('provider.weekdays'));
        $payload = [
            'daily_capacity' => 60,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'pickup_notes' => null,
        ];
        foreach ($weekdays as $day) {
            $payload['operating_hours'][$day] = ['closed' => true];
        }

        $before = Activity::query()->count();

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), $payload);
        $response->assertRedirect(route('provider.profile.edit'));

        $this->assertSame($before + 1, Activity::query()->count());
        $latest = Activity::query()->latest('id')->first();
        $this->assertSame('provider_operating_info.updated', $latest->description);
    }

    #[Test]
    public function business_profile_update_logs_activity(): void
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

        $before = Activity::query()->count();

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

        $this->assertSame($before + 1, Activity::query()->count());
        $latest = Activity::query()->latest('id')->first();
        $this->assertSame('provider_profile.business_updated', $latest->description);
    }

    #[Test]
    public function financial_profile_update_logs_activity_without_raw_iban_in_properties(): void
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

        $before = Activity::query()->count();

        $response = $this->actingAs($provider)->patch(route('profile.provider-financial.update'), [
            'bank_name' => 'Al Rajhi Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'New Holder Name',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $this->assertSame($before + 1, Activity::query()->count());
        $latest = Activity::query()->latest('id')->first();
        $this->assertSame('provider_financial.updated', $latest->description);
        $props = $latest->properties?->toArray() ?? [];
        $this->assertArrayNotHasKey('iban', $props);
        $this->assertTrue($props['iban_updated'] ?? false);
    }
}
