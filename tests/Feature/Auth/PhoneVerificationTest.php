<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        config(['app.phone_verification_enabled' => true]);
    }

    public function test_registration_redirects_to_verify_phone_when_enabled(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'donor',
            'name' => 'Test Donor',
            'phone_number' => '0501234567',
            'email' => 'donor@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.phone'));
        $this->assertDatabaseHas('users', [
            'email' => 'donor@example.com',
            'phone_number' => '966501234567',
        ]);
        $this->assertNull(User::where('email', 'donor@example.com')->first()->phone_verified_at);
    }

    public function test_verify_phone_page_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => null,
        ]);
        $user->assignRole('donor');

        $response = $this->actingAs($user)->get(route('verification.phone'));

        $response->assertStatus(200);
    }

    public function test_otp_verification_succeeds_and_redirects(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => null,
        ]);
        $user->assignRole('donor');

        $otp = '123456';
        Cache::put('otp:user:' . $user->id, $otp, now()->addMinutes(5));

        $response = $this->actingAs($user)->post(route('verification.phone.verify'), [
            'otp' => $otp,
        ]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_otp_verification_fails_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => null,
        ]);
        $user->assignRole('donor');

        Cache::put('otp:user:' . $user->id, '123456', now()->addMinutes(5));

        $response = $this->actingAs($user)->post(route('verification.phone.verify'), [
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_duplicate_phone_rejected_on_registration(): void
    {
        User::factory()->create([
            'phone_number' => '966501234567',
            'email' => 'existing@example.com',
        ])->assignRole('donor');

        $response = $this->post('/register', [
            'membership_type' => 'donor',
            'name' => 'New Donor',
            'phone_number' => '0501234567',
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('phone_number');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_redirects_to_verify_phone_when_not_verified(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => null,
        ]);
        $user->assignRole('donor');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.phone'));
    }
}
