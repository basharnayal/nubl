<?php

namespace Tests\Feature\Auth;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProviderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_provider_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register.provider'));

        $response->assertStatus(200);
    }

    public function test_provider_application_page_redirects_when_profile_is_missing(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $this->actingAs($provider)
            ->get(route('provider.application'))
            ->assertRedirect(route('register.provider'));
    }

    public function test_provider_application_page_displays_existing_application_data(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'phone_number' => '966500123123',
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => 'Provider',
            'phone_number' => '966500123123',
            'email' => $provider->email,
            'business_name_ar' => 'Provider Shop',
            'business_name_en' => 'Provider Shop',
            'unified_number' => '7000000555',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'location' => null,
        ]);
        ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => ['sun' => ['closed' => true]],
            'daily_capacity' => 20,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'pickup_notes' => null,
        ]);
        ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'Provider',
        ]);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'provider_documents/license.pdf',
            'id_or_iqama_path' => 'provider_documents/id.pdf',
        ]);

        $response = $this->actingAs($provider)->get(route('provider.application'));

        $response->assertOk();
        $response->assertViewIs('auth.provider-application');
        $response->assertViewHas('providerData', function (array $providerData) use ($provider): bool {
            return ($providerData['profile']->user_id ?? null) === $provider->id;
        });
    }

    public function test_new_providers_can_register(): void
    {
        $businessLicense = UploadedFile::fake()->create('license.pdf', 100, 'application/pdf');
        $idDocument = UploadedFile::fake()->create('id.pdf', 100, 'application/pdf');

        $operatingHours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $operatingHours[$day] = ['closed' => false, 'open' => '09:00', 'close' => '17:00'];
        }

        $response = $this->post('/register/provider', array_merge(
            [
                'full_name_ar' => 'مقدم خدمة تجريبي',
                'full_name_en' => 'Test Provider',
                'phone_number' => '0501112233',
                'email' => 'provider@example.com',
                'business_name_ar' => 'مطعم تجريبي',
                'business_name_en' => 'Test Restaurant',
                'unified_number' => '1234567890',
                'business_category' => ['restaurant'],
                'address_ar' => 'عنوان تجريبي',
                'address_en' => 'Test Address',
                'city' => 'medina',
                'region' => 'western',
                'location' => null,
                'operating_hours' => $operatingHours,
                'daily_capacity' => 50,
                'service_type' => ['meal_preparation', 'delivery'],
                'estimated_preparation_order_time' => '30 minutes',
                'adoption_support' => 'yes',
                'bank_name' => 'Test Bank',
                'iban' => 'SA0380000000608010167519',
                'account_holder_name' => 'Test Provider',
                'password' => 'password',
                'business_license' => $businessLicense,
                'id_or_iqama' => $idDocument,
            ]
        ));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'provider@example.com',
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_PENDING_APPROVAL,
            'phone_number' => '966501112233',
        ]);

        $user = User::where('email', 'provider@example.com')->first();
        $this->assertNotNull($user->providerProfile);
        $this->assertNotNull($user->providerOperatingInfo);
        $this->assertNotNull($user->providerFinancialInfo);
        $this->assertNotNull($user->providerDocuments);

        $this->assertDatabaseHas('provider_profiles', [
            'user_id' => $user->id,
            'business_name_en' => 'Test Restaurant',
            'unified_number' => '1234567890',
        ]);

        $response->assertRedirect(route('approval.pending'));
    }
}
