<?php

namespace Tests\Unit\Donor;

use App\Http\Services\AllocationService;
use App\Http\Services\AuditService;
use App\Models\Ewallet;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\User;
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
        $audit->shouldReceive('log')->once();
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
        $audit->shouldNotReceive('log');
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient funds');

        $service->allocateToRequest($request->id, 100.0);
    }
}
