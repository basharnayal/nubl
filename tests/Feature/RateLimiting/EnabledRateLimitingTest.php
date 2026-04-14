<?php

namespace Tests\Feature\RateLimiting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnabledRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['rate_limiting.enabled' => true]);
    }

    #[Test]
    public function login_route_uses_enabled_named_limiter(): void
    {
        config(['rate_limiting.login.per_minute' => 2]);

        $payload = [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ];

        $this->post(route('login'), $payload)->assertSessionHasErrors('email');
        $this->post(route('login'), $payload)->assertSessionHasErrors('email');
        $this->post(route('login'), $payload)->assertStatus(429);
    }

    #[Test]
    public function payment_gateway_callback_uses_enabled_named_limiter(): void
    {
        config(['rate_limiting.payments_gateway.per_minute' => 2]);

        $this->get(route('payments.callback'))->assertRedirect(route('donor.payments.failed'));
        $this->get(route('payments.callback'))->assertRedirect(route('donor.payments.failed'));
        $this->get(route('payments.callback'))->assertStatus(429);
    }

    #[Test]
    public function donor_payment_initiation_uses_enabled_named_limiter(): void
    {
        config(['rate_limiting.donor_payments.per_minute' => 2]);

        $donor = $this->userWithRole('donor');

        $this->actingAs($donor)
            ->post(route('donor.payments.initiate'), ['amount' => 0])
            ->assertSessionHasErrors('amount');
        $this->actingAs($donor)
            ->post(route('donor.payments.initiate'), ['amount' => 0])
            ->assertSessionHasErrors('amount');
        $this->actingAs($donor)
            ->post(route('donor.payments.initiate'), ['amount' => 0])
            ->assertStatus(429);
    }

    #[Test]
    public function provider_qr_redeem_route_limits_repeated_invalid_tokens(): void
    {
        $provider = $this->userWithRole('provider');

        $this->actingAs($provider)
            ->postJson(route('provider.qr.redeem'), ['token' => 'bad-token'])
            ->assertStatus(404);
        $this->actingAs($provider)
            ->postJson(route('provider.qr.redeem'), ['token' => 'bad-token'])
            ->assertStatus(404);
        $this->actingAs($provider)
            ->postJson(route('provider.qr.redeem'), ['token' => 'bad-token'])
            ->assertStatus(429)
            ->assertJsonFragment(['error' => __('Too many attempts, wait 30 seconds.')]);
    }

    private function userWithRole(string $roleName): User
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => $roleName === 'provider' ? User::MEMBERSHIP_PROVIDER : User::MEMBERSHIP_DONOR,
        ]);
        $user->assignRole($roleName);

        return $user;
    }
}
