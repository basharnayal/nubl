<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;
    protected $recipient;
    protected $menuItem;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        // Users
        $this->provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->provider->assignRole('provider');

        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->recipient->assignRole('recipient');

        // Menu Item
        $this->menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Burger',
            'price' => 50.00,
            'is_active' => true,
        ]);

        // Create a Pending Request
        $this->request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'PENDING',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->request->items()->create([
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 50.00,
        ]);
    }

    /** @test */
    public function provider_can_view_incoming_requests()
    {
        $response = $this->actingAs($this->provider)
            ->get(route('provider.requests.index'));

        $response->assertStatus(200);
        $response->assertSee($this->recipient->name);
        $response->assertSee('50.00');
    }

    /** @test */
    public function provider_can_adopt_request()
    {
        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'adopt',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'ADOPTED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);
    }

    /** @test */
    public function provider_can_approve_request()
    {
        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'approve',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'PROVIDER_APPROVED',
            'funding_source' => 'CITY_FUND',
        ]);
    }

    /** @test */
    public function provider_can_reject_request_with_reason()
    {
        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'reject',
                'rejection_reason_code' => 'Item Unavailable',
                'rejection_reason_note' => 'Out of stock',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'PROVIDER_REJECTED',
            'rejection_reason_code' => 'Item Unavailable',
            'rejection_reason_note' => 'Out of stock',
        ]);
    }

    /** @test */
    public function provider_cannot_act_on_non_pending_request()
    {
        $this->request->update(['status' => 'FULFILLED']);

        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'reject',
                'rejection_reason_code' => 'Other',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error'); // Controller should return back with error

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'FULFILLED', // Should not change
        ]);
    }

    /** @test */
    public function provider_cannot_view_others_requests()
    {
        $otherProvider = User::factory()->create();
        $otherProvider->assignRole('provider');

        $response = $this->actingAs($otherProvider)
            ->get(route('provider.requests.show', $this->request->id));

        $response->assertStatus(404); // Scoped query should not find it
    }
}
