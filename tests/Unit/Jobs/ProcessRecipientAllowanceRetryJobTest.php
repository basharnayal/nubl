<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessRecipientAllowanceRetryJob;
use App\Jobs\ProcessRecipientFundRetryJob;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AuditService;
use App\Services\RecipientRequestSubmissionService;
use App\Support\RecipientAllowanceRetryCache;
use App\Support\RecipientFundRetryCache;
use App\Support\RecipientRequestSubmitCooldown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessRecipientAllowanceRetryJobTest extends TestCase
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
        $user = User::factory()->create();
        RecipientAllowanceRetryCache::clear($user->id);
        RecipientRequestSubmitCooldown::clear($user->id);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldNotReceive('computeLineItems');
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldNotReceive('log');

        (new ProcessRecipientAllowanceRetryJob($user->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientAllowanceRetryCache::getPayload($user->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($user->id));
    }

    #[Test]
    public function it_clears_retry_state_when_target_user_no_longer_exists(): void
    {
        $userId = 999_999;
        $payload = $this->payload();

        RecipientAllowanceRetryCache::storePayload($userId, $payload);
        RecipientRequestSubmitCooldown::start($userId, 60);
        RecipientAllowanceRetryCache::tryScheduleJobLock($userId, 60);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldNotReceive('computeLineItems');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldNotReceive('log');

        (new ProcessRecipientAllowanceRetryJob($userId))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientAllowanceRetryCache::getPayload($userId));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($userId));
        $this->assertTrue(RecipientAllowanceRetryCache::tryScheduleJobLock($userId, 5));
    }

    #[Test]
    public function it_aborts_and_logs_when_item_computation_throws(): void
    {
        $recipient = User::factory()->create();
        $payload = $this->payload();

        RecipientAllowanceRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 60);
        RecipientAllowanceRetryCache::tryScheduleJobLock($recipient->id, 60);
        Log::spy();

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(77, $payload['items'])
            ->andThrow(new \RuntimeException('menu changed'));
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('canCoverRequestAmount');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'allowance_retry_aborted',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && ($data['reason'] ?? null) === 'menu changed'),
                $recipient->id
            );

        (new ProcessRecipientAllowanceRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                'allowance_retry_aborted',
                Mockery::on(fn (array $ctx): bool => ($ctx['user_id'] ?? null) === $recipient->id
                    && ($ctx['message'] ?? null) === 'menu changed')
            );

        $this->assertNull(RecipientAllowanceRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
        $this->assertTrue(RecipientAllowanceRetryCache::tryScheduleJobLock($recipient->id, 5));
    }

    #[Test]
    public function it_logs_still_exceeded_and_clears_retry_state_when_allowance_is_not_freed(): void
    {
        config(['recipient.weekly_allowance_limit' => 10]);

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientAllowanceRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 60);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(77, $payload['items'])
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
                'allowance_retry_still_exceeded',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && (float) ($data['amount'] ?? 0) === 20.0),
                $recipient->id
            );

        (new ProcessRecipientAllowanceRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientAllowanceRetryCache::getPayload($recipient->id));
        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    #[Test]
    public function it_queues_fund_retry_and_starts_cooldown_when_city_fund_is_insufficient_and_lock_is_available(): void
    {
        config(['recipient.weekly_allowance_limit' => 1000]);
        config(['recipient.fund_retry_delay_seconds' => 45]);
        Queue::fake();

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientAllowanceRetryCache::storePayload($recipient->id, $payload);
        RecipientFundRetryCache::clear($recipient->id);
        RecipientRequestSubmitCooldown::clear($recipient->id);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(77, $payload['items'])
            ->andReturn([
                'total' => 90.0,
                'requestItemsPayload' => [],
            ]);
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('canCoverRequestAmount')
            ->once()
            ->with(90.0)
            ->andReturn(false);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'fund_retry_queued_from_allowance_job',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && (float) ($data['amount'] ?? 0) === 90.0),
                $recipient->id
            );

        (new ProcessRecipientAllowanceRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        Queue::assertPushed(ProcessRecipientFundRetryJob::class, fn (ProcessRecipientFundRetryJob $job): bool => $job->userId === $recipient->id);
        $this->assertNull(RecipientAllowanceRetryCache::getPayload($recipient->id));
        $this->assertSame($payload, RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertTrue(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    #[Test]
    public function it_does_not_dispatch_fund_retry_when_lock_is_unavailable_but_still_tracks_retry_state(): void
    {
        config(['recipient.weekly_allowance_limit' => 1000]);
        config(['recipient.fund_retry_delay_seconds' => 30]);
        Queue::fake();

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientAllowanceRetryCache::storePayload($recipient->id, $payload);
        RecipientFundRetryCache::clear($recipient->id);
        RecipientRequestSubmitCooldown::clear($recipient->id);
        $this->assertTrue(RecipientFundRetryCache::tryScheduleJobLock($recipient->id, 120));

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(77, $payload['items'])
            ->andReturn([
                'total' => 55.0,
                'requestItemsPayload' => [],
            ]);
        $submissionService->shouldNotReceive('createRequest');

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('canCoverRequestAmount')
            ->once()
            ->with(55.0)
            ->andReturn(false);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'fund_retry_queued_from_allowance_job',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && (float) ($data['amount'] ?? 0) === 55.0),
                $recipient->id
            );

        (new ProcessRecipientAllowanceRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        Queue::assertNotPushed(ProcessRecipientFundRetryJob::class);
        $this->assertNull(RecipientAllowanceRetryCache::getPayload($recipient->id));
        $this->assertSame($payload, RecipientFundRetryCache::getPayload($recipient->id));
        $this->assertTrue(RecipientRequestSubmitCooldown::active($recipient->id));
    }

    #[Test]
    public function it_creates_request_and_clears_retry_state_when_all_checks_pass(): void
    {
        config(['recipient.weekly_allowance_limit' => 1000]);

        $recipient = User::factory()->create();
        $payload = $this->payload();
        RecipientAllowanceRetryCache::storePayload($recipient->id, $payload);
        RecipientRequestSubmitCooldown::start($recipient->id, 20);

        $submissionService = Mockery::mock(RecipientRequestSubmissionService::class);
        $submissionService->shouldReceive('computeLineItems')
            ->once()
            ->with(77, $payload['items'])
            ->andReturn([
                'total' => 30.0,
                'requestItemsPayload' => [],
            ]);
        $submissionService->shouldReceive('createRequest')
            ->once()
            ->with(
                Mockery::on(fn (User $user): bool => $user->id === $recipient->id),
                77,
                $payload['items']
            );

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('canCoverRequestAmount')
            ->once()
            ->with(30.0)
            ->andReturn(true);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with(
                'request',
                'allowance_retry_succeeded',
                Mockery::on(fn (array $data): bool => ($data['recipient_id'] ?? null) === $recipient->id
                    && ($data['provider_id'] ?? null) === 77
                    && (float) ($data['amount'] ?? 0) === 30.0),
                $recipient->id
            );

        (new ProcessRecipientAllowanceRetryJob($recipient->id))
            ->handle($submissionService, $allocationService, $auditService);

        $this->assertNull(RecipientAllowanceRetryCache::getPayload($recipient->id));
        $this->assertFalse(RecipientRequestSubmitCooldown::active($recipient->id));
        $this->assertNull(RecipientFundRetryCache::getPayload($recipient->id));
    }

    /**
     * @return array{provider_id: int, items: array<int, array{id: int, quantity: int}>}
     */
    private function payload(): array
    {
        return [
            'provider_id' => 77,
            'items' => [
                ['id' => 10, 'quantity' => 2],
                ['id' => 11, 'quantity' => 1],
            ],
        ];
    }
}
