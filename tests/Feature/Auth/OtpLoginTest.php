<?php

namespace Tests\Feature\Auth;

use App\Http\Services\SmsService;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        config(['app.phone_verification_enabled' => false]);
    }

    public function test_otp_login_request_succeeds_and_shows_verify_step(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andReturn(true);
        });

        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('donor');

        $response = $this->post(route('login.otp.request'), [
            'phone' => '0501234567',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('otp_phone', '966501234567');
        $response->assertSessionHas('otp_status');
    }

    public function test_otp_login_request_fails_for_invalid_phone(): void
    {
        $response = $this->post(route('login.otp.request'), [
            'phone' => 'invalid',
        ]);

        $response->assertSessionHasErrors('otp_phone');
    }

    public function test_otp_login_request_fails_for_nonexistent_phone(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldNotReceive('send');
        });

        $response = $this->post(route('login.otp.request'), [
            'phone' => '0509999999',
        ]);

        $response->assertSessionHasErrors('otp_phone');
    }

    public function test_otp_login_verify_succeeds_and_authenticates(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('donor');

        Cache::put('otp:login:966501234567', '123456', now()->addMinutes(5));

        $response = $this->post(route('login.otp.verify'), [
            'otp_phone' => '966501234567',
            'otp_code' => '123456',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_otp_login_verify_fails_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('donor');

        Cache::put('otp:login:966501234567', '123456', now()->addMinutes(5));

        $response = $this->post(route('login.otp.verify'), [
            'otp_phone' => '966501234567',
            'otp_code' => '999999',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('otp_code');
    }

    public function test_otp_login_verify_fails_with_expired_code(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('donor');

        Cache::put('otp:login:966501234567', '123456', now()->addSeconds(-1));

        $response = $this->post(route('login.otp.verify'), [
            'otp_phone' => '966501234567',
            'otp_code' => '123456',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('otp_code');
    }

    public function test_deactivated_user_cannot_login_via_otp(): void
    {
        $user = User::factory()->inactive()->create([
            'phone_number' => '966501234567',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('donor');

        Cache::put('otp:login:966501234567', '123456', now()->addMinutes(5));

        $response = $this->post(route('login.otp.verify'), [
            'otp_phone' => '966501234567',
            'otp_code' => '123456',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('otp_code');
    }
}
