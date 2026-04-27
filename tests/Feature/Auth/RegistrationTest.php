<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** Valid 1x1 PNG for base64 image validation */
    private const VALID_BASE64_IMAGE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_donors_can_register(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'donor',
            'name' => 'Test Donor',
            'phone_number' => '0501234567',
            'email' => 'donor@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'donor@example.com',
            'membership_type' => 'donor',
            'status' => 'active',
            'phone_number' => '966501234567',
        ]);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_recipients_can_register(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'recipient',
            'name' => 'Test Recipient',
            'phone_number' => '0509876543',
            'email' => 'recipient@example.com',
            'password' => 'password',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Riyadh - Al Olaya - King Fahd - 12',
            'id_type' => 'national_id',
            'id_number' => '1234567890',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'location_lat' => 24.7136,
            'location_lng' => 46.6753,
            'income_band' => '1000-1500',
            'household_size' => 4,
            'marital_status' => 'married',
            'is_student' => false,
            'employment_status' => 'unemployed',
            'situation_description' => 'Lost job six months ago, supporting four family members with no income.',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'recipient@example.com',
            'membership_type' => 'recipient',
            'status' => User::STATUS_PENDING_APPROVAL,
            'phone_number' => '966509876543',
        ]);
        $this->assertDatabaseHas('recipient_profiles', [
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Riyadh - Al Olaya - King Fahd - 12',
            'id_type' => 'national_id',
            'id_number' => '1234567890',
        ]);
        $this->assertDatabaseHas('recipient_kyc_details', [
            'income_band' => '1000-1500',
            'household_size' => 4,
            'marital_status' => 'married',
            'is_student' => false,
            'employment_status' => 'unemployed',
        ]);
        $response->assertRedirect(route('approval.pending'));
    }

    public function test_recipients_can_register_with_hudood_number(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'recipient',
            'name' => 'Test Hudood',
            'phone_number' => '0501111222',
            'email' => 'hudood@example.com',
            'password' => 'password',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Jeddah - Al Rawdah - Prince Sultan - 5',
            'id_type' => 'hudood_number',
            'id_number' => '2345678901',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'location_lat' => 21.4858,
            'location_lng' => 39.1925,
            'income_band' => '0-500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'employment_status' => 'unable_to_work',
            'situation_description' => 'Unable to work due to health issues and requires food assistance.',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('recipient_profiles', [
            'id_type' => 'hudood_number',
            'id_number' => '2345678901',
        ]);
        $this->assertDatabaseHas('recipient_kyc_details', [
            'employment_status' => 'unable_to_work',
        ]);
    }

    public function test_recipient_registration_requires_gps_location(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'recipient',
            'name' => 'Test Recipient',
            'phone_number' => '0502222333',
            'email' => 'nogps@example.com',
            'password' => 'password',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Riyadh - Al Olaya - King Fahd - 12',
            'id_type' => 'national_id',
            'id_number' => '1234567890',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            // location_lat and location_lng intentionally omitted
            'income_band' => '1000-1500',
            'household_size' => 3,
            'marital_status' => 'single',
            'is_student' => false,
            'employment_status' => 'unemployed',
            'situation_description' => 'Need assistance due to unemployment.',
        ]);

        $response->assertSessionHasErrors(['location_lat', 'location_lng']);
        $this->assertGuest();
    }

    public function test_recipient_registration_requires_id_number(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'recipient',
            'name' => 'Test Recipient',
            'phone_number' => '0503334444',
            'email' => 'noid@example.com',
            'password' => 'password',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Riyadh - Al Olaya - King Fahd - 12',
            'id_type' => 'national_id',
            // id_number intentionally omitted
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'location_lat' => 24.7136,
            'location_lng' => 46.6753,
            'income_band' => '1000-1500',
            'household_size' => 3,
            'marital_status' => 'single',
            'is_student' => false,
            'employment_status' => 'unemployed',
            'situation_description' => 'Need assistance due to unemployment.',
        ]);

        $response->assertSessionHasErrors('id_number');
        $this->assertGuest();
    }

    public function test_recipient_id_number_must_be_10_digits(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'recipient',
            'name' => 'Test Recipient',
            'phone_number' => '0504445555',
            'email' => 'shortid@example.com',
            'password' => 'password',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Riyadh - Al Olaya - King Fahd - 12',
            'id_type' => 'national_id',
            'id_number' => '12345',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'location_lat' => 24.7136,
            'location_lng' => 46.6753,
            'income_band' => '1000-1500',
            'household_size' => 3,
            'marital_status' => 'single',
            'is_student' => false,
            'employment_status' => 'unemployed',
            'situation_description' => 'Need assistance due to unemployment.',
        ]);

        $response->assertSessionHasErrors('id_number');
        $this->assertGuest();
    }

    public function test_registration_requires_membership_type(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('membership_type');
        $this->assertGuest();
    }
}
