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
            'short_address' => '123 Test Street, Riyadh',
            'id_type' => 'national_id',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'income_band' => '1000-1500',
            'household_size' => 4,
            'marital_status' => 'married',
            'is_student' => false,
            'address_confirmation_base64' => self::VALID_BASE64_IMAGE,
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
            'short_address' => '123 Test Street, Riyadh',
            'id_type' => 'national_id',
        ]);
        $this->assertDatabaseHas('recipient_kyc_details', [
            'income_band' => '1000-1500',
            'household_size' => 4,
            'marital_status' => 'married',
            'is_student' => false,
        ]);
        $response->assertRedirect(route('approval.pending'));
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
