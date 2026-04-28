<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function generate_returns_six_digit_numeric_string(): void
    {
        $sms = Mockery::mock(SmsService::class);
        $service = new OtpService($sms);

        $otp = $service->generate();

        $this->assertSame(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    #[Test]
    public function verify_otp_returns_false_for_wrong_code(): void
    {
        Cache::flush();

        $sms = Mockery::mock(SmsService::class);
        $service = new OtpService($sms);
        $user = User::factory()->create();

        Cache::put('otp:user:'.$user->id, '123456', now()->addMinutes(5));

        $this->assertFalse($service->verifyOtp($user, '000000'));
        $this->assertNotNull(Cache::get('otp:user:'.$user->id));
    }

    #[Test]
    public function verify_otp_returns_true_and_clears_cache_on_match(): void
    {
        Cache::flush();

        $sms = Mockery::mock(SmsService::class);
        $service = new OtpService($sms);
        $user = User::factory()->create();

        Cache::put('otp:user:'.$user->id, '654321', now()->addMinutes(5));

        $this->assertTrue($service->verifyOtp($user, '654321'));
        $this->assertNull(Cache::get('otp:user:'.$user->id));
    }

    #[Test]
    public function send_otp_fails_when_user_has_no_phone(): void
    {
        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $service = new OtpService($sms);
        $user = User::factory()->create(['phone_number' => null]);

        $result = $service->sendOtp($user);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function send_otp_succeeds_and_stores_code_in_cache(): void
    {
        Cache::flush();

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(true);
        $service = new OtpService($sms);
        $user = User::factory()->create(['phone_number' => '966501234567']);

        $result = $service->sendOtp($user);

        $this->assertTrue($result['success']);
        $stored = Cache::get('otp:user:'.$user->id);
        $this->assertNotNull($stored, 'OTP must be stored in cache after send.');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $stored);
    }

    #[Test]
    public function send_otp_blocked_when_resend_limit_exceeded(): void
    {
        Cache::flush();

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $service = new OtpService($sms);
        $user = User::factory()->create(['phone_number' => '966501234567']);

        // Pre-fill the resend counter at the limit
        Cache::put('otp:resend:'.$user->id, OtpService::RESEND_LIMIT, now()->addMinutes(60));

        $result = $service->sendOtp($user);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function send_otp_returns_failure_message_when_sms_send_fails(): void
    {
        Cache::flush();
        Log::spy();

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(false);
        $service = new OtpService($sms);
        $user = User::factory()->create(['phone_number' => '966501234567']);

        $result = $service->sendOtp($user);

        $this->assertFalse($result['success']);
        $this->assertSame((string) __('Failed to send verification code. Please try again.'), $result['message']);
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'OtpService: SMS send failed; OTP not logged for security'
                && $context['user_id'] === $user->id
                && $context['phone'] === '966*******67'
        );
    }

    #[Test]
    public function send_otp_for_login_rejects_invalid_phone_before_cache_or_sms(): void
    {
        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $service = new OtpService($sms);

        $result = $service->sendOtpForLogin('invalid');

        $this->assertFalse($result['success']);
        $this->assertSame((string) __('Invalid phone number.'), $result['message']);
    }

    #[Test]
    public function send_otp_for_login_respects_resend_limit(): void
    {
        Cache::flush();

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $service = new OtpService($sms);

        Cache::put('otp:login:resend:966501234567', OtpService::RESEND_LIMIT, now()->addMinutes(60));

        $result = $service->sendOtpForLogin('0501234567');

        $this->assertFalse($result['success']);
        $this->assertSame((string) __('Too many attempts. Please try again later.'), $result['message']);
    }

    #[Test]
    public function send_otp_for_login_logs_when_sms_send_fails(): void
    {
        Cache::flush();
        Log::spy();

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(false);
        $service = new OtpService($sms);

        $result = $service->sendOtpForLogin('0501234567');

        $this->assertFalse($result['success']);
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'OtpService: login SMS send failed; OTP not logged for security'
                && $context['phone'] === '966*******67'
        );
    }

    #[Test]
    public function verify_otp_for_login_returns_null_for_malformed_code(): void
    {
        $sms = Mockery::mock(SmsService::class);
        $service = new OtpService($sms);

        $this->assertNull($service->verifyOtpForLogin('0501234567', '12'));
    }

    #[Test]
    public function verify_otp_returns_false_for_malformed_or_missing_code(): void
    {
        Cache::flush();

        $sms = Mockery::mock(SmsService::class);
        $service = new OtpService($sms);
        $user = User::factory()->create();

        $this->assertFalse($service->verifyOtp($user, '12'));
        $this->assertFalse($service->verifyOtp($user, '123456'));
    }
}
