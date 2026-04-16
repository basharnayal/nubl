<?php

namespace Tests\Feature\Provider;

use App\Models\Request as RequestModel;
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
        $response->assertSee('Welcome back,');
        $response->assertSee('OPEN');
        $response->assertSee('Pause Store');
        $response->assertSee(__('provider.dashboard.kpi_section'), false);
        $response->assertSee('Awaiting your response', false);
        $response->assertSee(__('provider.dashboard.adoptions_title'), false);
        $response->assertSee(__('provider.dashboard.fulfilled_30d'), false);
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

    #[Test]
    public function dashboard_shows_adopted_requests_count_for_provider_as_donor(): void
    {
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $recipient->assignRole('recipient');

        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 25.00,
            'status' => 'APPROVED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);

        $response = $this->actingAs($this->provider)
            ->get(route('provider.dashboard'));

        $response->assertOk();
        $response->assertSee('data-metric="adopted-donor-count"', false);
        $response->assertSee(__('provider.dashboard.adoptions_title'), false);

        $this->assertSame(
            1,
            RequestModel::query()
                ->where('provider_id', $this->provider->id)
                ->where('funding_source', 'PROVIDER_ADOPTION')
                ->count()
        );
    }

    #[Test]
    public function provider_requests_index_accepts_funding_source_filter_for_adoptions(): void
    {
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $recipient->assignRole('recipient');

        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 10.00,
            'status' => 'APPROVED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);
        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 10.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        $response = $this->actingAs($this->provider)
            ->get(route('provider.requests.index', ['funding_source' => 'PROVIDER_ADOPTION']));

        $response->assertOk();
        $requests = $response->viewData('requests');
        $this->assertSame(1, $requests->total());
    }
}
