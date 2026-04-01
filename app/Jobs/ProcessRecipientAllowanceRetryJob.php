<?php

namespace App\Jobs;

use App\Http\Services\AuditService;
use App\Http\Services\RecipientAllowanceService;
use App\Http\Services\RecipientRequestSubmissionService;
use App\Models\User;
use App\Support\RecipientAllowanceRetryCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FR-6.4: Re-attempt recipient request creation after weekly allowance was temporarily exceeded.
 */
class ProcessRecipientAllowanceRetryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId
    ) {}

    public function handle(
        RecipientRequestSubmissionService $submissionService,
        AuditService $auditService
    ): void {
        $payload = RecipientAllowanceRetryCache::getPayload($this->userId);

        if ($payload === null) {
            return;
        }

        $user = User::find($this->userId);

        if ($user === null) {
            RecipientAllowanceRetryCache::clear($this->userId);

            return;
        }

        $providerId = (int) $payload['provider_id'];
        $itemsData = $payload['items'];

        try {
            $computed = $submissionService->computeLineItems($providerId, $itemsData);
        } catch (Throwable $e) {
            Log::warning('allowance_retry_aborted', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
            ]);
            $auditService->log('request', 'allowance_retry_aborted', [
                'recipient_id' => $this->userId,
                'reason' => $e->getMessage(),
            ], $this->userId);
            RecipientAllowanceRetryCache::clear($this->userId);

            return;
        }

        $totalAmount = $computed['total'];

        if (RecipientAllowanceService::wouldExceedAllowance($this->userId, $totalAmount)) {
            $auditService->log('request', 'allowance_retry_still_exceeded', [
                'recipient_id' => $this->userId,
                'amount' => $totalAmount,
            ], $this->userId);
            RecipientAllowanceRetryCache::clear($this->userId);

            return;
        }

        $submissionService->createRequest($user, $providerId, $itemsData);

        $auditService->log('request', 'allowance_retry_succeeded', [
            'recipient_id' => $this->userId,
            'provider_id' => $providerId,
            'amount' => $totalAmount,
        ], $this->userId);

        RecipientAllowanceRetryCache::clear($this->userId);
    }
}
