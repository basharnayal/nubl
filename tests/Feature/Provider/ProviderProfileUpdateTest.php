<?php

namespace Tests\Feature\Provider;

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
}
