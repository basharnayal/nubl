<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessPendingAllocationsJob;
use App\Models\PendingAllocation;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessPendingAllocationsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_processes_all_pending_allocations_and_removes_processed_rows(): void
    {
        $providerA = User::factory()->create();
        $providerB = User::factory()->create();

        $pendingA = $this->createPendingAllocation($providerA, 40.00);
        $pendingB = $this->createPendingAllocation($providerB, 25.00);

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('allocateToRequest')->once()->with($pendingA->request_id, 40.0);
        $allocationService->shouldReceive('allocateToRequest')->once()->with($pendingB->request_id, 25.0);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'allocation_engine',
                'pending_batch_processed',
                Mockery::on(fn (array $data): bool => ($data['processed'] ?? null) === 2
                    && ($data['failed'] ?? null) === 0
                    && ($data['provider_id'] ?? null) === null)
            );

        (new ProcessPendingAllocationsJob)->handle($allocationService, $auditService);

        $this->assertDatabaseCount('pending_allocations', 0);
    }

    #[Test]
    public function it_processes_only_selected_provider_when_provider_id_is_supplied(): void
    {
        $providerA = User::factory()->create();
        $providerB = User::factory()->create();

        $pendingA = $this->createPendingAllocation($providerA, 50.00);
        $this->createPendingAllocation($providerB, 35.00);

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('allocateToRequest')->once()->with($pendingA->request_id, 50.0);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'allocation_engine',
                'pending_batch_processed',
                Mockery::on(fn (array $data): bool => ($data['processed'] ?? null) === 1
                    && ($data['failed'] ?? null) === 0
                    && ($data['provider_id'] ?? null) === $providerA->id)
            );

        (new ProcessPendingAllocationsJob($providerA->id))->handle($allocationService, $auditService);

        $this->assertDatabaseCount('pending_allocations', 1);
        $this->assertDatabaseHas('pending_allocations', ['provider_id' => $providerB->id]);
    }

    #[Test]
    public function it_keeps_failed_rows_and_logs_failure_context(): void
    {
        $provider = User::factory()->create();
        $pending = $this->createPendingAllocation($provider, 75.00);

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('allocateToRequest')
            ->once()
            ->with($pending->request_id, 75.0)
            ->andThrow(new \RuntimeException('allocation failed'));

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'allocation',
                'pending_retry_failed',
                Mockery::on(fn (array $data): bool => ($data['pending_allocation_id'] ?? null) === $pending->id
                    && ($data['request_id'] ?? null) === $pending->request_id)
            );
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'allocation_engine',
                'pending_batch_processed',
                Mockery::on(fn (array $data): bool => ($data['processed'] ?? null) === 0
                    && ($data['failed'] ?? null) === 1)
            );

        (new ProcessPendingAllocationsJob)->handle($allocationService, $auditService);

        $this->assertDatabaseHas('pending_allocations', ['id' => $pending->id]);
    }

    private function createPendingAllocation(User $provider, float $amount): PendingAllocation
    {
        $recipient = User::factory()->create();

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => $amount,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        return PendingAllocation::create([
            'request_id' => $request->id,
            'provider_id' => $provider->id,
            'amount' => $amount,
            'paused_by' => 'global',
        ]);
    }
}
