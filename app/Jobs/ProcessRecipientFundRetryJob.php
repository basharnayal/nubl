<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AllocationService;
use App\Services\AuditService;
use App\Services\RecipientAllowanceService;
use App\Services\RecipientRequestSubmissionService;
use App\Support\RecipientFundRetryCache;
use App\Support\RecipientRequestSubmitCooldown;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FR-6.2 / FR-6.4: Re-attempt recipient request creation when city fund had insufficient pooled payments.
 */
class ProcessRecipientFundRetryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId
    ) {}

    public function handle(
        RecipientRequestSubmissionService $submissionService,
        AllocationService $allocationService,
        AuditService $auditService
    ): void {
        $payload = RecipientFundRetryCache::getPayload($this->userId);

        if ($payload === null) {
            return;
        }

        $user = User::find($this->userId);

        if ($user === null) {
            RecipientFundRetryCache::clear($this->userId);
            RecipientRequestSubmitCooldown::clear($this->userId);

            return;
        }

        $providerId = (int) $payload['provider_id'];
        $itemsData = $payload['items'];

        try {
            $computed = $submissionService->computeLineItems($providerId, $itemsData);
        } catch (Throwable $e) {
            Log::warning('fund_retry_aborted', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
            ]);
            $auditService->log('request', 'fund_retry_aborted', [
                'recipient_id' => $this->userId,
                'reason' => $e->getMessage(),
            ], $this->userId);
            RecipientFundRetryCache::clear($this->userId);
            RecipientRequestSubmitCooldown::clear($this->userId);

            return;
        }

        $totalAmount = $computed['total'];

        if (RecipientAllowanceService::wouldExceedAllowance($this->userId, $totalAmount)) {
            $auditService->log('request', 'fund_retry_skipped_allowance', [
                'recipient_id' => $this->userId,
                'amount' => $totalAmount,
            ], $this->userId);
            RecipientFundRetryCache::clear($this->userId);
            RecipientRequestSubmitCooldown::clear($this->userId);

            return;
        }

        if (! $allocationService->canCoverRequestAmount($totalAmount)) {
            $auditService->log('request', 'fund_retry_still_insufficient', [
                'recipient_id' => $this->userId,
                'amount' => $totalAmount,
            ], $this->userId);
            RecipientFundRetryCache::clear($this->userId);
            RecipientRequestSubmitCooldown::clear($this->userId);

            return;
        }

        $submissionService->createRequest($user, $providerId, $itemsData);

        $auditService->log('request', 'fund_retry_succeeded', [
            'recipient_id' => $this->userId,
            'provider_id' => $providerId,
            'amount' => $totalAmount,
        ], $this->userId);

        RecipientFundRetryCache::clear($this->userId);
        RecipientRequestSubmitCooldown::clear($this->userId);
    }
}
