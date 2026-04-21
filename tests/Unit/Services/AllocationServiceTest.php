<?php

namespace Tests\Unit\Services;

use App\Models\Ewallet;
use App\Models\Payment;
use App\Models\PendingAllocation;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function allocate_to_request_is_idempotent_for_the_same_request(): void
    {
        $request = $this->createRequestWithSucceededPayment(100.00, 60.00);
        $audit = Mockery::spy(AuditService::class);
        $service = new AllocationService($audit);

        $service->allocateToRequest($request->id, 60.00);
        $service->allocateToRequest($request->id, 60.00);

        $this->assertSame(1, RequestPaymentLink::where('request_id', $request->id)->count());
        $this->assertSame('60.00', (string) RequestPaymentLink::where('request_id', $request->id)->firstOrFail()->amount);

        $audit->shouldHaveReceived('log')
            ->with(
                'allocation',
                'created',
                Mockery::on(fn (array $data): bool => ($data['request_id'] ?? null) === $request->id
                    && ($data['amount'] ?? null) === 60.0
                    && ($data['already_allocated'] ?? null) === 0.0),
                null
            )
            ->once();
    }

    #[Test]
    public function paused_allocation_updates_existing_pending_row_instead_of_duplicating_it(): void
    {
        SystemSetting::setValue('allocation_engine.paused', '1');

        $request = $this->createRequestWithSucceededPayment(100.00, 60.00);
        $audit = Mockery::spy(AuditService::class);
        $service = new AllocationService($audit);

        $service->allocateToRequest($request->id, 60.00);
        $service->allocateToRequest($request->id, 60.00);

        $this->assertSame(0, RequestPaymentLink::where('request_id', $request->id)->count());
        $this->assertSame(1, PendingAllocation::where('request_id', $request->id)->count());
        $this->assertSame('60.00', (string) PendingAllocation::where('request_id', $request->id)->firstOrFail()->amount);
    }

    #[Test]
    public function allocate_zero_amount_does_not_touch_database_or_audit(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldNotReceive('log');
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationService::class);
        $service->allocateToRequest(1, 0.0);

        $this->assertSame(0, RequestPaymentLink::query()->count());
    }

    #[Test]
    public function allocate_splits_across_payments_fifo(): void
    {
        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $recipient = User::factory()->create();
        $provider = User::factory()->create();
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Item',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);
        $request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 50.00,
        ]);

        $donor = User::factory()->create();
        $p1 = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 40,
        ]);
        $p2 = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 60,
        ]);

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('allocation', 'created', Mockery::on(function (array $data) use ($request) {
                return ($data['request_id'] ?? null) === $request->id
                    && abs((float) ($data['amount'] ?? 0) - 50.0) < 0.001;
            }), Mockery::any());
        // isPaused() path also calls log('allocation', 'queued_pending') — but only if paused; not here
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationService::class);
        $service->allocateToRequest($request->id, 50.0);

        $this->assertDatabaseHas('request_payment_links', [
            'request_id' => $request->id,
            'payment_id' => $p1->id,
            'amount' => 40,
        ]);
        $this->assertDatabaseHas('request_payment_links', [
            'request_id' => $request->id,
            'payment_id' => $p2->id,
            'amount' => 10,
        ]);

        // Exactly two links — no more, no less
        $this->assertSame(2, RequestPaymentLink::where('request_id', $request->id)->count());
    }

    #[Test]
    public function allocate_throws_when_insufficient_succeeded_payment_balance(): void
    {
        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $recipient = User::factory()->create();
        $provider = User::factory()->create();
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Item',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 100.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);
        $request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 100.00,
        ]);

        $donor = User::factory()->create();
        Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 10,
        ]);

        $audit = Mockery::mock(AuditService::class);
        // No 'allocation.created' audit should fire when funds are insufficient;
        // 'allocation.queued_pending' also must not fire (engine not paused here).
        $audit->shouldNotReceive('log');
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationService::class);

        $this->expectException(\RuntimeException::class);
        // Production message: 'Insufficient funds in city fund to allocate for this request.'
        $this->expectExceptionMessage('Insufficient funds');

        $service->allocateToRequest($request->id, 100.0);
    }

    #[Test]
    public function can_cover_request_amount_matches_unallocated_pool(): void
    {
        $donor = User::factory()->create();
        Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 40,
        ]);
        Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 60,
        ]);

        $service = app(AllocationService::class);

        $this->assertSame(100.0, $service->availableCityFundAmount());
        $this->assertTrue($service->canCoverRequestAmount(100.0));
        $this->assertFalse($service->canCoverRequestAmount(100.01));
    }

    private function createRequestWithSucceededPayment(float $paymentAmount, float $requestAmount): RequestModel
    {
        Payment::factory()->succeeded()->create(['amount' => $paymentAmount]);

        $recipient = User::factory()->create();
        $provider = User::factory()->create();

        return RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => $requestAmount,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);
    }
}
