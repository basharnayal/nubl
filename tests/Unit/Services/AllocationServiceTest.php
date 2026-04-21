<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PendingAllocation;
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
