<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PendingAllocation;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\SystemSetting;
use App\Support\FinancialMath;
use Illuminate\Support\Facades\DB;

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
        return (float) $this->availableCityFundAmountString();
    }

    /**
     * Unallocated SAR across succeeded donor payments (FIFO pool), as a decimal string.
     */
    private function availableCityFundAmountString(): string
    {
        $payments = Payment::where('status', Payment::STATUS_SUCCEEDED)
            ->orderBy('created_at')
            ->get();

        $total = '0.00';

        foreach ($payments as $payment) {
            $used = FinancialMath::normalize(
                (string) RequestPaymentLink::where('payment_id', $payment->id)->sum('amount')
            );
            $available = FinancialMath::sub(
                FinancialMath::normalize((string) ($payment->getRawOriginal('amount') ?? $payment->amount)),
                $used
            );

            if (FinancialMath::compare($available, '0.00') > 0) {
                $total = FinancialMath::add($total, $available);
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

        return FinancialMath::compare(
            $this->availableCityFundAmountString(),
            FinancialMath::normalize($amount)
        ) >= 0;
    }

    /**
     * Allocate amount to a request from available payments (FIFO).
     * Creates request_payment_links rows. If the engine is paused (globally or
     * per-provider), queues the unallocated remainder into pending_allocations (FR-24.2).
     *
     * @throws \RuntimeException If insufficient funds across payments
     */
    public function allocateToRequest(int $requestId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($requestId, $amount): void {
            $targetAmount = FinancialMath::normalize($amount);
            $request = RequestModel::query()->whereKey($requestId)->lockForUpdate()->firstOrFail();

            $alreadyAllocated = FinancialMath::normalize(
                (string) RequestPaymentLink::where('request_id', $requestId)->sum('amount')
            );
            $remaining = FinancialMath::sub($targetAmount, $alreadyAllocated);

            if (FinancialMath::compare($remaining, '0.00') <= 0) {
                return;
            }

            if ($this->isPaused($request, $remaining)) {
                return;
            }

            $payments = Payment::where('status', Payment::STATUS_SUCCEEDED)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $allocations = [];

            foreach ($payments as $payment) {
                if (FinancialMath::compare($remaining, '0.00') <= 0) {
                    break;
                }

                $used = FinancialMath::normalize(
                    (string) RequestPaymentLink::where('payment_id', $payment->id)->sum('amount')
                );
                $available = FinancialMath::sub(
                    FinancialMath::normalize((string) ($payment->getRawOriginal('amount') ?? $payment->amount)),
                    $used
                );

                if (FinancialMath::compare($available, '0.00') <= 0) {
                    continue;
                }

                $allocate = FinancialMath::min($remaining, $available);
                $allocations[] = [
                    'payment_id' => $payment->id,
                    'amount' => $allocate,
                ];
                $remaining = FinancialMath::sub($remaining, $allocate);
            }

            if (FinancialMath::compare($remaining, '0.00') > 0) {
                throw new \RuntimeException('Insufficient funds in city fund to allocate for this request.');
            }

            foreach ($allocations as $alloc) {
                RequestPaymentLink::create([
                    'payment_id' => $alloc['payment_id'],
                    'request_id' => $requestId,
                    'amount' => $alloc['amount'],
                ]);
            }

            $allocatedNow = FinancialMath::sub($targetAmount, $alreadyAllocated);
            $this->auditService->log('allocation', 'created', [
                'request_id' => $requestId,
                'amount' => (float) $allocatedNow,
                'target_amount' => (float) $targetAmount,
                'already_allocated' => (float) $alreadyAllocated,
                'allocations' => $allocations,
            ], auth()->id());
        });
    }

    /**
     * Check if allocation is paused globally or for the request's provider.
     * If paused, stores the currently unallocated remainder and returns true.
     */
    private function isPaused(RequestModel $request, string $amount): bool
    {
        $globallyPaused = SystemSetting::getValue(self::GLOBAL_PAUSE_KEY) === '1';
        $providerPaused = $request->provider && $request->provider->allocation_paused;

        if (! $globallyPaused && ! $providerPaused) {
            return false;
        }

        $pausedBy = $globallyPaused ? 'global' : 'provider';

        PendingAllocation::updateOrCreate([
            'request_id' => $request->id,
        ], [
            'provider_id' => $request->provider_id,
            'amount' => $amount,
            'paused_by' => $pausedBy,
        ]);

        $this->auditService->log('allocation', 'queued_pending', [
            'request_id' => $request->id,
            'amount' => (float) $amount,
            'paused_by' => $pausedBy,
        ], auth()->id());

        return true;
    }
}
