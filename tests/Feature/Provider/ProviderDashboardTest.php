<?php

namespace Tests\Feature\Provider;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');

        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $this->provider->assignRole('provider');
    }

    #[Test]
    public function provider_can_view_store_status_on_dashboard()
    {
        $response = $this->actingAs($this->provider)
            ->get(route('provider.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Store Status');
        $response->assertSee('OPEN');
        $response->assertSee('Pause Store');
        $response->assertSee('Awaiting your response', false);
        $response->assertSee('Fulfilled (30 days)', false);
        $response->assertSee('Fulfilled orders by week', false);
        $response->assertSee('Recent requests', false);
        $response->assertSee('View all', false);
    }

    #[Test]
    public function provider_can_toggle_accepting_orders_without_changing_account_active_flag()
    {
        $this->assertTrue($this->provider->accepting_orders);
        $this->assertTrue($this->provider->is_active);

        $response = $this->actingAs($this->provider)
            ->post(route('provider.profile.toggle-active'));

        $response->assertRedirect();
        $this->provider->refresh();
        $this->assertFalse($this->provider->accepting_orders);
        $this->assertTrue($this->provider->is_active, 'Admin account flag is_active must stay unchanged when pausing store');

        $response = $this->actingAs($this->provider->fresh())
            ->get(route('provider.dashboard'));
        $response->assertSee('PAUSED');
        $response->assertSee('Open Store');

        $this->actingAs($this->provider->fresh())
            ->post(route('provider.profile.toggle-active'));

        $this->provider->refresh();
        $this->assertTrue($this->provider->accepting_orders);
    }
}
