<?php

namespace Tests\Feature\Recipient;

use App\Jobs\ProcessRecipientAllowanceRetryJob;
use App\Jobs\ProcessRecipientFundRetryJob;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\ProviderOperatingInfo;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipientRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected $recipient;

    protected $provider;

    protected $menuItem1;

    protected $menuItem2;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Setup Roles
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        // Create Recipient
        $this->recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->recipient->assignRole('recipient');

        // Create Provider
        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => 'provider',
        ]);
        $this->provider->assignRole('provider');

        // Create Provider Operating Info
        ProviderOperatingInfo::create([
            'user_id' => $this->provider->id,
            'daily_capacity' => 50,
            'operating_hours' => [],
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 mins',
        ]);

        // Create Menu Items
        $this->menuItem1 = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Burger',
            'description' => 'Delicious',
            'price' => 50.00,
            'category' => 'Meals',
            'is_active' => true,
        ]);

        $this->menuItem2 = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Fries',
            'description' => 'Crispy',
            'price' => 20.00,
            'category' => 'Sides',
            'is_active' => true,
        ]);

        // FR-6.2: pooled donor payments must cover city-fund requests at submit time
        Payment::factory()->create([
            'amount' => 100_000.00,
            'status' => Payment::STATUS_SUCCEEDED,
        ]);
    }

    #[Test]
    public function recipient_can_submit_multi_item_request_successfully()
    {
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2], // 100
                    ['id' => $this->menuItem2->id, 'quantity' => 1], // 20
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Check Header
        $this->assertDatabaseHas('requests', [
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 120.00, // (50*2) + (20*1)
            'status' => 'REQUESTED',
        ]);

        // Check Items
        $request = RequestModel::where('recipient_id', $this->recipient->id)->first();
        $this->assertCount(2, $request->items);

        $this->assertDatabaseHas('request_items', [
            'request_id' => $request->id,
            'menu_item_id' => $this->menuItem1->id,
            'quantity' => 2,
            'price_snapshot' => 50.00,
        ]);

        $this->assertDatabaseHas('request_items', [
            'request_id' => $request->id,
            'menu_item_id' => $this->menuItem2->id,
            'quantity' => 1,
            'price_snapshot' => 20.00,
        ]);

        $this->provider->refresh();
        $this->assertCount(1, $this->provider->notifications);
        $this->assertSame('provider_new_request', $this->provider->notifications->first()->data['type'] ?? null);
        $this->assertSame($request->id, $this->provider->notifications->first()->data['request_id'] ?? null);
    }

    #[Test]
    public function weekly_allowance_exceeded_queues_retry_job_fr_6_4()
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00')); // Wednesday

        // Existing REDEEMABLE request: 300 SAR (counts towards allowance)
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 300.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ])->items()->create([
            'menu_item_id' => $this->menuItem1->id,
            'quantity' => 6,
            'price_snapshot' => 50.00,
        ]);

        // Try to add 120 SAR (300 + 120 = 420 > 400)
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2], // 100
                    ['id' => $this->menuItem2->id, 'quantity' => 1], // 20
                ],
            ]);

        $response->assertSessionHas('info');
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 1); // Only the setup one — no immediate row

        Queue::assertPushed(ProcessRecipientAllowanceRetryJob::class, function (ProcessRecipientAllowanceRetryJob $job) {
            return $job->userId === $this->recipient->id;
        });
    }

    #[Test]
    public function submit_cooldown_blocks_new_request_after_allowance_retry_queued(): void
    {
        config(['recipient.allowance_retry_delay_seconds' => 2]);
        config(['recipient.fund_retry_delay_seconds' => 2]);
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 300.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ])->items()->create([
            'menu_item_id' => $this->menuItem1->id,
            'quantity' => 6,
            'price_snapshot' => 50.00,
        ]);

        $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2],
                    ['id' => $this->menuItem2->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('requests', 1);

        $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem2->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('requests', 1);

        $this->travel(3)->seconds();

        $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem2->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('requests', 2);

        Carbon::setTestNow();
        $this->travelBack();
    }

    #[Test]
    public function city_fund_insufficient_queues_fund_retry_job_fr_6_4(): void
    {
        Queue::fake();
        Payment::query()->delete();

        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHas('info');
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 0);

        Queue::assertPushed(ProcessRecipientFundRetryJob::class, function (ProcessRecipientFundRetryJob $job) {
            return $job->userId === $this->recipient->id;
        });

        Carbon::setTestNow();
    }

    #[Test]
    public function allowance_retry_job_creates_request_when_weekly_allowance_frees()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        $existingReq = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 300.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);
        $existingReq->items()->create([
            'menu_item_id' => $this->menuItem1->id,
            'quantity' => 6,
            'price_snapshot' => 50.00,
        ]);

        Queue::fake();

        $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2],
                    ['id' => $this->menuItem2->id, 'quantity' => 1],
                ],
            ]);

        Queue::assertPushed(ProcessRecipientAllowanceRetryJob::class);

        // Free allowance (e.g. prior order cancelled) before retry runs
        $existingReq->update(['status' => 'CANCELLED']);

        $job = new ProcessRecipientAllowanceRetryJob($this->recipient->id);
        $job->handle(
            app(\App\Http\Services\RecipientRequestSubmissionService::class),
            app(\App\Http\Services\AllocationService::class),
            app(\App\Http\Services\AuditService::class)
        );

        $this->assertDatabaseCount('requests', 2);
        $new = RequestModel::where('recipient_id', $this->recipient->id)->orderByDesc('id')->first();
        $this->assertSame('REQUESTED', $new->status);
        $this->assertEquals(120.00, (float) $new->reserved_amount);

        $this->provider->refresh();
        $this->assertCount(1, $this->provider->notifications);
        $this->assertSame('provider_new_request', $this->provider->notifications->first()->data['type'] ?? null);
    }

    #[Test]
    public function rejected_request_does_not_count_towards_allowance()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        // High value rejected request
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 300.00,
            'status' => 'ADMIN_REJECTED', // Should NOT count
            'funding_source' => 'CITY_FUND',
        ]);

        // Try to add 150 SAR (0 + 150 < 400)
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 3], // 150
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 2);
    }

    #[Test]
    public function adopted_request_does_not_count_towards_allowance()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        // Adopted request (Provider pays) - APPROVED + PROVIDER_ADOPTION, does NOT count toward allowance
        RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 350.00,
            'status' => 'APPROVED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);

        // Try to add 100 SAR (0 + 100 < 400)
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2], // 100
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 2);
    }

    #[Test]
    public function fulfilled_provider_adopted_request_does_not_count_towards_allowance()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));

        $adopted = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 350.00,
            'status' => 'FULFILLED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);
        $adopted->items()->create([
            'menu_item_id' => $this->menuItem1->id,
            'quantity' => 7,
            'price_snapshot' => 50.00,
        ]);

        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $this->provider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 2],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('requests', 2);
    }

    #[Test]
    public function cannot_request_menu_item_not_belonging_to_provider()
    {
        $otherProvider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $otherProvider->assignRole('provider');

        // Setup other provider capacity
        ProviderOperatingInfo::create([
            'user_id' => $otherProvider->id,
            'daily_capacity' => 10,
            'operating_hours' => [],
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30',
        ]);

        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.store'), [
                'provider_id' => $otherProvider->id,
                'items' => [
                    ['id' => $this->menuItem1->id, 'quantity' => 1], // Belongs to Provider A
                ],
            ]);

        $response->assertSessionHasErrors(['items.0.id']);
        $this->assertDatabaseCount('requests', 0);
    }
}
