<?php

namespace Tests\Feature\Auth;

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
