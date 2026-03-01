<?php

namespace App\Http\Services;

use App\Models\Payment;
use App\Models\RequestPaymentLink;

class AllocationService
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Allocate amount to a request from available payments (FIFO).
     * Creates request_payment_links rows. Must be called inside DB::transaction.
     *
     * @throws \RuntimeException If insufficient funds across payments
     */
    public function allocateToRequest(int $requestId, float $amount): void
    {
        if ($amount <= 0) {
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
}
