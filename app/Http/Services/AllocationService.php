<?php

namespace App\Http\Services;

use App\Models\Payment;
use App\Models\PendingAllocation;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\SystemSetting;

class AllocationService
{
    private const GLOBAL_PAUSE_KEY = 'allocation_engine.paused';

    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Total unallocated SAR across succeeded donor payments (FIFO pool for city fund).
     * Matches the pool used by allocateToRequest before links are created.
     */
    public function availableCityFundAmount(): float
    {
        $payments = Payment::where('status', Payment::STATUS_SUCCEEDED)
            ->orderBy('created_at')
            ->get();

        $total = 0.0;

        foreach ($payments as $payment) {
            $used = (float) RequestPaymentLink::where('payment_id', $payment->id)->sum('amount');
            $available = (float) $payment->amount - $used;

            if ($available > 0) {
                $total += $available;
            }
        }

        return $total;
    }

    /**
     * Whether the pooled city fund can cover this amount (FR-6.2 at submit time).
     */
    public function canCoverRequestAmount(float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return $this->availableCityFundAmount() + 0.001 >= $amount;
    }

    /**
     * Allocate amount to a request from available payments (FIFO).
     * Creates request_payment_links rows. Must be called inside DB::transaction.
     * If the engine is paused (globally or per-provider), queues into pending_allocations (FR-24.2).
     *
     * @throws \RuntimeException If insufficient funds across payments
     */
    public function allocateToRequest(int $requestId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        // FR-24.1 / FR-24.2: guard — queue if paused, do not throw
        if ($this->isPaused($requestId, $amount)) {
            return;
        }

        $payments = Payment::where('status', Payment::STATUS_SUCCEEDED)
            ->orderBy('created_at')
            ->get();

        $remaining = $amount;
        $allocations = [];

        foreach ($payments as $payment) {
            if ($remaining <= 0) {
                break;
            }

            $used = (float) RequestPaymentLink::where('payment_id', $payment->id)->sum('amount');
            $available = (float) $payment->amount - $used;

            if ($available <= 0) {
                continue;
            }

            $allocate = min($remaining, $available);
            $allocations[] = [
                'payment_id' => $payment->id,
                'amount' => $allocate,
            ];
            $remaining -= $allocate;
        }

        if ($remaining > 0.001) {
            throw new \RuntimeException('Insufficient funds in city fund to allocate for this request.');
        }

        foreach ($allocations as $alloc) {
            RequestPaymentLink::create([
                'payment_id' => $alloc['payment_id'],
                'request_id' => $requestId,
                'amount' => $alloc['amount'],
            ]);
        }

        $this->auditService->log('allocation', 'created', [
            'request_id' => $requestId,
            'amount' => $amount,
            'allocations' => $allocations,
        ], auth()->id());
    }

    /**
     * Check if allocation is paused globally or for the request's provider.
     * If paused, stores into pending_allocations and returns true.
     */
    private function isPaused(int $requestId, float $amount): bool
    {
        $globallyPaused = SystemSetting::getValue(self::GLOBAL_PAUSE_KEY) === '1';

        $request = RequestModel::find($requestId);
        $providerPaused = $request && $request->provider && $request->provider->allocation_paused;

        if (! $globallyPaused && ! $providerPaused) {
            return false;
        }

        $pausedBy = $globallyPaused ? 'global' : 'provider';

        PendingAllocation::create([
            'request_id' => $requestId,
            'provider_id' => $request->provider_id,
            'amount' => $amount,
            'paused_by' => $pausedBy,
        ]);

        $this->auditService->log('allocation', 'queued_pending', [
            'request_id' => $requestId,
            'amount' => $amount,
            'paused_by' => $pausedBy,
        ], auth()->id());

        return true;
    }
}
