<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        $stored = Cache::get('otp:user:' . $user->id);
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
        Cache::put('otp:resend:' . $user->id, OtpService::RESEND_LIMIT, now()->addMinutes(60));

        $result = $service->sendOtp($user);

        $this->assertFalse($result['success']);
    }
}
