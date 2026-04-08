<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SmsService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OtpLoginRoleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        config([
            'app.phone_verification_enabled' => false,
            'app.email_verification_enabled' => false,
        ]);
    }

    private function mockSuccessfulSms(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(true);
        });
    }

    private function localPhoneFor(User $user): string
    {
        return '0' . substr((string) $user->phone_number, 3);
    }

    private function requestAndVerifyOtp(User $user)
    {
        $requestResponse = $this->post(route('login.otp.request'), [
            'phone' => $this->localPhoneFor($user),
        ]);

        $requestResponse->assertRedirect();
        $requestResponse->assertSessionHas('otp_phone', $user->phone_number);
        $requestResponse->assertSessionHas('otp_status');

        $otpCode = Cache::get('otp:login:' . $user->phone_number);
        $this->assertNotNull($otpCode, 'Expected login OTP to be stored in cache.');

        return $this->post(route('login.otp.verify'), [
            'otp_phone' => $user->phone_number,
            'otp_code' => $otpCode,
        ]);
    }

    public function test_donor_can_request_and_verify_phone_login_and_reaches_dashboard(): void
    {
        $this->mockSuccessfulSms();

        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => '966501234567',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('donor');

        $verifyResponse = $this->requestAndVerifyOtp($user);

        $this->assertAuthenticatedAs($user);
        $verifyResponse->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_pending_recipient_can_request_and_verify_phone_login_and_reaches_approval_pending(): void
    {
        $this->mockSuccessfulSms();

        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_PENDING_APPROVAL,
            'phone_number' => '966509876543',
            'phone_verified_at' => now(),
        ]);
        $user->assignRole('recipient');

        $verifyResponse = $this->requestAndVerifyOtp($user);

        $this->assertAuthenticatedAs($user);
        $verifyResponse->assertRedirect(route('approval.pending'));
    }

    public function test_pending_provider_can_request_and_verify_phone_login_and_reaches_approval_pending(): void
    {
        $this->mockSuccessfulSms();

        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_PENDING_APPROVAL,
            'phone_number' => '966501112233',
            'phone_verified_at' => now(),
            'accepting_orders' => true,
        ]);
        $user->assignRole('provider');

        $verifyResponse = $this->requestAndVerifyOtp($user);

        $this->assertAuthenticatedAs($user);
        $verifyResponse->assertRedirect(route('approval.pending'));
    }
}
