<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\AllocationEngineStatusChangedNotification;
use App\Services\AllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllocationPauseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $provider;

    protected User $recipient;

    protected User $donor;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->provider->assignRole('provider');

        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->recipient->assignRole('recipient');

        $this->donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->donor->assignRole('donor');
    }

    private function seedPayment(string $gatewayId): Payment
    {
        return Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => $gatewayId,
            'amount' => 200.00,
            'status' => Payment::STATUS_SUCCEEDED,
        ]);
    }

    // ─── FR-24.1 Global ────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_pause_allocation_engine_globally(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.allocation.pause'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('1', SystemSetting::getValue('allocation_engine.paused'));

        $activity = Activity::where('description', 'allocation_engine.paused_globally')->first();
        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);

        $this->assertSame(1, $this->provider->notifications()->count());
        $dbNotification = $this->provider->notifications()->first();
        $this->assertSame(AllocationEngineStatusChangedNotification::class, $dbNotification->type);
        $payload = $dbNotification->data;
        $this->assertSame('paused_globally_provider', $payload['event']);
        $this->assertSame(route('provider.dashboard'), $payload['url']);
    }

    #[Test]
    public function admin_can_resume_allocation_engine_globally(): void
    {
        SystemSetting::setValue('allocation_engine.paused', '1');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.allocation.resume'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('0', SystemSetting::getValue('allocation_engine.paused'));

        $activity = Activity::where('description', 'allocation_engine.resumed_globally')->first();
        $this->assertNotNull($activity);

        $this->assertSame(1, $this->provider->notifications()->count());
        $payload = $this->provider->notifications()->first()->data;
        $this->assertSame('resumed_globally_provider', $payload['event']);
        $this->assertSame(route('provider.dashboard'), $payload['url']);
    }

    // ─── FR-24.1 Per-provider ──────────────────────────────────────────────────

    #[Test]
    public function admin_can_pause_allocation_for_specific_provider(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.allocation.provider.pause', $this->provider));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->provider->refresh();
        $this->assertTrue((bool) $this->provider->allocation_paused);

        $payload = $this->provider->notifications()->latest()->first()->data;
        $this->assertSame('paused', $payload['event']);
        $this->assertSame(route('provider.dashboard'), $payload['url']);

        $activity = Activity::where('description', 'allocation_engine.paused_for_provider')->first();
        $this->assertNotNull($activity);
        $this->assertSame($this->provider->id, $activity->properties->get('provider_id'));
    }

    #[Test]
    public function admin_can_resume_allocation_for_specific_provider(): void
    {
        $this->provider->update(['allocation_paused' => true]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.allocation.provider.resume', $this->provider));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->provider->refresh();
        $this->assertFalse((bool) $this->provider->allocation_paused);
    }

    // ─── FR-24.2 Queue state ───────────────────────────────────────────────────

    #[Test]
    public function allocation_is_queued_when_engine_is_globally_paused(): void
    {
        SystemSetting::setValue('allocation_engine.paused', '1');

        $request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->seedPayment('test-001');

        app(AllocationService::class)->allocateToRequest($request->id, 50.00);

        // Should NOT create payment links — queued instead
        $this->assertDatabaseCount('request_payment_links', 0);
        $this->assertDatabaseHas('pending_allocations', [
            'request_id' => $request->id,
            'amount' => 50.00,
            'paused_by' => 'global',
        ]);
    }

    #[Test]
    public function allocation_is_queued_when_provider_is_paused(): void
    {
        $this->provider->update(['allocation_paused' => true]);

        $request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->seedPayment('test-002');

        app(AllocationService::class)->allocateToRequest($request->id, 50.00);

        $this->assertDatabaseCount('request_payment_links', 0);
        $this->assertDatabaseHas('pending_allocations', [
            'request_id' => $request->id,
            'paused_by' => 'provider',
        ]);
    }

    #[Test]
    public function pending_allocations_processed_on_global_resume(): void
    {
        SystemSetting::setValue('allocation_engine.paused', '1');

        $request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->seedPayment('test-003');

        // Queue the allocation while paused
        app(AllocationService::class)->allocateToRequest($request->id, 50.00);
        $this->assertDatabaseCount('pending_allocations', 1);

        // Resume (job runs synchronously in test via QUEUE_CONNECTION=sync)
        $this->actingAs($this->admin)
            ->post(route('admin.allocation.resume'));

        // FR-24.2: pending_allocations empty + payment link created
        $this->assertDatabaseCount('pending_allocations', 0);
        $this->assertDatabaseHas('request_payment_links', [
            'request_id' => $request->id,
            'amount' => 50.00,
        ]);
    }

    #[Test]
    public function provider_pause_does_not_affect_other_providers(): void
    {
        $otherProvider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $otherProvider->assignRole('provider');

        $this->provider->update(['allocation_paused' => true]);

        $request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $otherProvider->id, // different provider
            'reserved_amount' => 30.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->seedPayment('test-004');

        // Other provider is NOT paused → allocation should succeed
        app(AllocationService::class)->allocateToRequest($request->id, 30.00);

        $this->assertDatabaseCount('pending_allocations', 0);
        $this->assertDatabaseHas('request_payment_links', [
            'request_id' => $request->id,
            'amount' => 30.00,
        ]);
    }
}
