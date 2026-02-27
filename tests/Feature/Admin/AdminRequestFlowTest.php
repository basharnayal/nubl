<?php

namespace Tests\Feature\Admin;

use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $provider;
    protected $recipient;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        // Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->provider->assignRole('provider');

        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->recipient->assignRole('recipient');

        // Menu Item
        $item = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Item',
            'price' => 100.00,
            'is_active' => true,
        ]);

        // Create Request Pending Admin
        $this->request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 100.00,
            'status' => 'ADMIN_PENDING',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->request->items()->create([
            'menu_item_id' => $item->id,
            'quantity' => 1,
            'price_snapshot' => 100.00,
        ]);
    }

    /** @test */
    public function admin_can_view_request_queue()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.requests.index'));

        $response->assertStatus(200);
        $response->assertSee($this->provider->name);
        $response->assertSee($this->recipient->name);
    }

    /** @test */
    public function admin_can_approve_request()
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.requests.update', $this->request->id), [
                'action' => 'approve',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'ADMIN_APPROVED',
        ]);
    }

    /** @test */
    public function admin_can_reject_request()
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.requests.update', $this->request->id), [
                'action' => 'reject',
                'rejection_reason_code' => 'Policy Violation',
                'rejection_reason_note' => 'Notes',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'ADMIN_REJECTED',
            'rejection_reason_code' => 'Policy Violation',
        ]);
    }

    /** @test */
    public function admin_cannot_act_on_non_admin_pending_request()
    {
        $this->request->update(['status' => 'REQUESTED']); // Provider level

        $response = $this->actingAs($this->admin)
            ->put(route('admin.requests.update', $this->request->id), [
                'action' => 'approve',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'REQUESTED',
        ]);
    }
}
