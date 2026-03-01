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
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->provider->assignRole('provider');
    }

    #[Test]
    public function provider_can_view_active_status_on_dashboard()
    {
        $response = $this->actingAs($this->provider)
            ->get(route('provider.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Store Status');
        $response->assertSee('ACTIVE'); // Currently true
        $response->assertSee('Pause Store');
    }

    #[Test]
    public function provider_can_toggle_active_status()
    {
        // Initial state: Active
        $this->assertTrue($this->provider->is_active);

        // Toggle to Inactive
        $response = $this->actingAs($this->provider)
            ->post(route('provider.profile.toggle-active'));

        $response->assertRedirect();
        $this->provider->refresh();
        $this->assertFalse($this->provider->is_active);

        // Check Dashboard reflects Inactive
        $response = $this->actingAs($this->provider->fresh())
            ->get(route('provider.dashboard'));
        $response->assertSee('INACTIVE');
        $response->assertSee('Activate Store');

        // Toggle back to Active
        $this->actingAs($this->provider->fresh())
            ->post(route('provider.profile.toggle-active'));

        $this->provider->refresh();
        $this->assertTrue($this->provider->is_active);
    }
}
