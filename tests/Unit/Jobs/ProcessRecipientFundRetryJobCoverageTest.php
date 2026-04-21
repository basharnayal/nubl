<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessRecipientFundRetryJob;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AuditService;
use App\Services\RecipientRequestSubmissionService;
use App\Support\RecipientFundRetryCache;
use App\Support\RecipientRequestSubmitCooldown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessRecipientFundRetryJobCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_early_when_retry_payload_is_missing(): void
    {
        $recipient = User::factory()->create();
        RecipientFundRetryCache::clear($recipient->id);
        RecipientRequestSubmitCooldown::clear($recipient->id);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldNotReceive('computeLineItems');
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldNotReceive('log');

        (new ProcessRecipientFundRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    #[Test]
    public function it_clears_retry_state_when_target_user_no_longer_exists(): void
    {
        $userId = 777_888;
        $payload = $this->payload();

        RecipientFundRetryCache::storePayload($userId, $payload);
        RecipientRequestSubmitCooldown::start($userId, 40);
        RecipientFundRetryCache::tryScheduleJobLock($userId, 60);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldNotReceive('computeLineItems');
        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');
        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldNotReceive('log');

        (new ProcessRecipientFundRetryJob($userId))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientFundRetryCache::getPayload($userId));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($userId));
        $this->assertTrue(RecipientFundRetryCache::tryScheduleJobLock($userId, 5));
    }

    #[Test]
    public function it_aborts_and_logs_when_item_computation_throws(): void
    {
        $recipient = User::factory()->create();
        $payload = $this->payload();

        RecipientFundRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 50);
        RecipientFundRetryCache::tryScheduleJobLock($recipient->id, 60);
        Log::spy();

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(91, $payload['items'])
            ->andThrow(new \RuntimeException('provider unavailable'));
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'fund_retry_aborted',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && ($data['reason'] ?? null) === 'provider unavailable'),
                $recipient->id
            );

        (new ProcessRecipientFundRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                'fund_retry_aborted',
                Mockery::on(fn (array $ctx): bool => ($ctx['user_id'] ?? null) === $recipient->id
                    && ($ctx['message'] ?? null) === 'provider unavailable')
            );

        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
        $this->assertTrue(RecipientFundRetryCache::tryScheduleJobLock($recipient->id, 5));
    }

    #[Test]
    public function it_skips_retry_when_allowance_is_now_exceeded(): void
    {
        config(['recipient.weekly_allowance_limit' => 10]);

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientFundRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 30);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(91, $payload['items'])
            ->andReturn([
                'total' => 20.0,
                'requestItemsPayload' => [],
            ]);
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'fund_retry_skipped_allowance',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && (float) ($data['amount'] ?? 0) === 20.0),
                $recipient->id
            );

        (new ProcessRecipientFundRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    #[Test]
    public function it_logs_and_clears_when_city_fund_is_still_insufficient(): void
    {
        config(['recipient.weekly_allowance_limit' => 1000]);

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientFundRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 30);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(91, $payload['items'])
            ->andReturn([
                'total' => 75.0,
                'requestItemsPayload' => [],
            ]);
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('canCoverRequestAmount')
            ->once()
            ->with(75.0)
            ->andReturn(false);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'fund_retry_still_insufficient',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && (float) ($data['amount'] ?? 0) === 75.0),
                $recipient->id
            );

        (new ProcessRecipientFundRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    #[Test]
    public function it_creates_request_when_all_checks_pass(): void
    {
        config(['recipient.weekly_allowance_limit' => 1000]);

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientFundRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 15);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(91, $payload['items'])
            ->andReturn([
                'total' => 40.0,
                'requestItemsPayload' => [],
            ]);
        $submissionService->shouldReceive('createRequest')
            ->once()
            ->with(
                Mockery::on(fn (User $user): bool => $user->id === $recipient->id),
                91,
                $payload['items']
            );

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('canCoverRequestAmount')
            ->once()
            ->with(40.0)
            ->andReturn(true);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'fund_retry_succeeded',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && ($data['provider_id'] ?? null) === 91
                    && (float) ($data['amount'] ?? 0) === 40.0),
                $recipient->id
            );

        (new ProcessRecipientFundRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    /**
     * @return array{provider_id: int, items: array<int, array{id: int, quantity: int}>}
     */
    private function payload(): array
    {
        return [
            'provider_id' => 91,
            'items' => [
                ['id' => 20, 'quantity' => 1],
                ['id' => 21, 'quantity' => 2],
            ],
        ];
    }
}

