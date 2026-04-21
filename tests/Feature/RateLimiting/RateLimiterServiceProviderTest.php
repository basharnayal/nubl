<?php

namespace Tests\Feature\RateLimiting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
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
}
