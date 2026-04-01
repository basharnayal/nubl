<?php

namespace Tests\Feature\Provider;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderQrRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->provider->assignRole('provider');
    }

    #[Test]
    public function invalid_token_returns_404_within_one_second_fr_9_2(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), [
                'token' => 'invalid-token-that-will-not-match',
            ]);

        $elapsed = microtime(true) - $start;

        $response->assertStatus(404);
        $response->assertJsonFragment(['error' => __('Invalid token.')]);
        $this->assertLessThan(1.0, $elapsed, 'Invalid token response should complete within 1 second (FR-9.2 smoke test).');
    }

    #[Test]
    public function rate_limit_allows_two_attempts_then_429_fr_9_3(): void
    {
        $token = 'x';

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $token])
            ->assertStatus(404);

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $token])
            ->assertStatus(404);

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $token])
            ->assertStatus(429)
            ->assertJsonFragment(['error' => __('Too many attempts, wait 30 seconds.')]);
    }
}
