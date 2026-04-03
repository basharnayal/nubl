<?php

namespace App\Jobs;

use App\Http\Services\AllocationService;
use App\Http\Services\AuditService;
use App\Models\PendingAllocation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * FR-24.2: Process all pending allocations after the engine is resumed.
 * Pass $providerId to process only that provider's pending rows (per-provider resume).
 * Pass null to process ALL pending rows (global resume).
 */
class ProcessPendingAllocationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?int $providerId = null
    ) {}

    public function handle(AllocationService $allocationService, AuditService $auditService): void
    {
        $query = PendingAllocation::query();

        if ($this->providerId !== null) {
            $query->where('provider_id', $this->providerId);
        }

        $pending = $query->get();

        if ($pending->isEmpty()) {
            return;
        }

        $processed = 0;
        $failed = 0;

        foreach ($pending as $item) {
            DB::beginTransaction();
            try {
                $allocationService->allocateToRequest($item->request_id, (float) $item->amount);
                $item->delete();
                DB::commit();
                $processed++;
            } catch (Throwable $e) {
                DB::rollBack();
                $failed++;
                $auditService->log('allocation', 'pending_retry_failed', [
                    'pending_allocation_id' => $item->id,
                    'request_id' => $item->request_id,
                    'amount' => $item->amount,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $auditService->log('allocation_engine', 'pending_batch_processed', [
            'provider_id' => $this->providerId,
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }
}
