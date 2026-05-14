<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
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

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        // Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->provider->assignRole('provider');

        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->recipient->assignRole('recipient');

        // System wallet required by admin requests index (city fund balance UI)
        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        // City fund pool for AllocationService::canCoverRequestAmount (admin approve)
        $donor = User::factory()->create();
        Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 500,
        ]);

        // Menu Item
        $item = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Item',
            'price' => 100.00,
            'category' => 'food',
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

    #[Test]
    public function admin_can_view_request_queue()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.requests.index'));

        $response->assertStatus(200);
        $response->assertSee($this->provider->name);
        $response->assertSee($this->recipient->name);
    }

    #[Test]
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

        $activity = Activity::where('causer_id', $this->admin->id)
            ->where('description', 'request.status_changed')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame('status_changed', $activity->properties->get('action'));
        $this->assertSame($this->request->id, $activity->properties->get('request_id'));
        $this->assertSame('ADMIN_PENDING', $activity->properties->get('from'));
        $this->assertSame('ADMIN_APPROVED', $activity->properties->get('to'));
        $this->assertSame('CITY_FUND', $activity->properties->get('funding_source'));
        $this->assertNotNull($activity->created_at);

        $this->provider->refresh();
        $this->assertCount(1, $this->provider->notifications);
        $this->assertSame('provider_request_status_changed', $this->provider->notifications->first()->data['type']);
        $this->assertSame('ADMIN_APPROVED', $this->provider->notifications->first()->data['status']);
    }

    #[Test]
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

        $activity = Activity::where('causer_id', $this->admin->id)
            ->where('description', 'request.status_changed')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame('status_changed', $activity->properties->get('action'));
        $this->assertSame('ADMIN_PENDING', $activity->properties->get('from'));
        $this->assertSame('ADMIN_REJECTED', $activity->properties->get('to'));
        $this->assertSame('Policy Violation', $activity->properties->get('rejection_reason_code'));
        $this->assertNotNull($activity->created_at);

        $this->provider->refresh();
        $this->assertCount(1, $this->provider->notifications);
        $this->assertSame('provider_request_status_changed', $this->provider->notifications->first()->data['type']);
        $this->assertSame('ADMIN_REJECTED', $this->provider->notifications->first()->data['status']);
    }

    #[Test]
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
