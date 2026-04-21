<?php

namespace Tests\Feature\RateLimiting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimiterServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['rate_limiting.enabled' => true]);
    }

    #[Test]
    public function registration_limiter_respects_configured_max_attempts(): void
    {
        config([
            'rate_limiting.registration.max_attempts' => 2,
            'rate_limiting.registration.decay_minutes' => 1,
        ]);

        $this->post(route('register'), [])->assertSessionHasErrors('membership_type');
        $this->post(route('register'), [])->assertSessionHasErrors('membership_type');
        $this->post(route('register'), [])->assertStatus(429);
    }

    #[Test]
    public function global_rate_limiting_toggle_disables_named_limiters(): void
    {
        config([
            'rate_limiting.enabled' => false,
            'rate_limiting.login.per_minute' => 1,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'missing@example.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }
    }

    #[Test]
    public function verification_limiter_applies_to_email_verification_notification_route(): void
    {
        config([
            'rate_limiting.enabled' => true,
            'rate_limiting.verification.per_minute' => 2,
        ]);

        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertStatus(429);
    }

    #[Test]
    public function named_limiters_resolve_expected_enabled_limits_and_keys(): void
    {
        config([
            'rate_limiting.enabled' => true,
            'rate_limiting.otp.per_minute' => 7,
            'rate_limiting.password_reset.decay_minutes' => 11,
            'rate_limiting.password_reset.max_attempts' => 3,
            'rate_limiting.sensitive_auth.per_minute' => 4,
            'rate_limiting.notifications.per_minute' => 123,
            'rate_limiting.profile_photo.per_minute' => 8,
            'rate_limiting.application_resubmit.decay_minutes' => 13,
            'rate_limiting.application_resubmit.max_attempts' => 5,
            'rate_limiting.verification.per_minute' => 9,
        ]);

        $user = User::factory()->create();

        $otp = $this->resolveLimiter('otp', $this->requestFrom('203.0.113.10'));
        $this->assertLimit($otp, 7, 60, '203.0.113.10');

        $passwordReset = $this->resolveLimiter('password_reset', $this->requestFrom('203.0.113.11'));
        $this->assertLimit($passwordReset, 3, 660, '203.0.113.11');

        $verification = $this->resolveLimiter('verification', $this->requestFrom('203.0.113.12', $user));
        $this->assertLimit($verification, 9, 60, 'uid:'.$user->id);

        $sensitiveAuth = $this->resolveLimiter('sensitive_auth', $this->requestFrom('203.0.113.13', $user));
        $this->assertLimit($sensitiveAuth, 4, 60, (string) $user->id);

        $notifications = $this->resolveLimiter('notifications', $this->requestFrom('203.0.113.14', $user));
        $this->assertLimit($notifications, 123, 60, (string) $user->id);

        $profilePhoto = $this->resolveLimiter('profile_photo', $this->requestFrom('203.0.113.15', $user));
        $this->assertLimit($profilePhoto, 8, 60, (string) $user->id);

        $applicationResubmit = $this->resolveLimiter('application_resubmit', $this->requestFrom('203.0.113.16', $user));
        $this->assertLimit($applicationResubmit, 5, 780, (string) $user->id);
    }

    private function resolveLimiter(string $name, Request $request): Limit
    {
        $limiter = RateLimiter::limiter($name);

        $this->assertIsCallable($limiter);

        return $limiter($request);
    }

    private function requestFrom(string $ip, ?User $user = null): Request
    {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ip]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function assertLimit(Limit $limit, int $maxAttempts, int $decaySeconds, string $key): void
    {
        $this->assertSame($maxAttempts, $limit->maxAttempts);
        $this->assertSame($decaySeconds, $limit->decaySeconds);
        $this->assertSame($key, $limit->key);
    }
}
