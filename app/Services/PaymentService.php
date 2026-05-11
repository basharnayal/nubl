<?php

namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\FundTransaction;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private MyFatoorahService $myFatoorah,
        private SystemWalletService $systemWallet,
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function initiateSponsorPayment(int $sponsorId, float $amount, bool $isAnonymous = false, ?string $idempotencyKey = null): Payment
    {
        if ($idempotencyKey !== null) {
            $existing = Payment::where('idempotency_key', $idempotencyKey)->first();

            if ($existing && $existing->status !== Payment::STATUS_FAILED) {
                return $existing;
            }
        }

        $payment = Payment::create([
            'sponsor_id' => $sponsorId,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_INITIATED,
            'amount' => $amount,
            'idempotency_key' => $idempotencyKey ?? Str::uuid()->toString(),
            'is_anonymous' => $isAnonymous,
        ]);

        $this->auditService->log('payment', 'initiated', [
            'payment_id' => $payment->id,
            'amount' => $amount,
            'sponsor_id' => $sponsorId,
            'is_anonymous' => $isAnonymous,
        ], $sponsorId);

        return $payment;
    }

    public function initiateGuestPayment(float $amount): Payment
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_INITIATED,
            'amount' => $amount,
            'idempotency_key' => Str::uuid()->toString(),
            'is_guest' => true,
            'is_anonymous' => true,
            'notes' => ['source' => 'quick_donation'],
        ]);

        $this->auditService->log('payment', 'initiated', [
            'payment_id' => $payment->id,
            'amount' => $amount,
            'is_guest' => true,
            'is_anonymous' => true,
            'source' => 'quick_donation',
        ], null);

        return $payment;
    }

    public function redirectToGateway(Payment $payment): RedirectResponse
    {
        $callbackUrl = route('payments.callback');
        $errorUrl = route('payments.error');
        $user = $payment->sponsor;

        try {
            $result = $this->myFatoorah->createInvoice(
                (float) $payment->amount,
                (string) $payment->id,
                $callbackUrl,
                $errorUrl,
                $user?->email,
                $user?->name
            );
        } catch (\Throwable $e) {
            $this->auditService->log('payment', 'gateway_api_error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $payment->sponsor_id);

            Log::critical('Payment critical failure: gateway API error', [
                'payment_id' => $payment->id,
                'sponsor_id' => $payment->sponsor_id,
                'amount' => $payment->amount,
                'error' => $e->getMessage(),
            ]);

            $payment->update(['status' => Payment::STATUS_FAILED]);

            return $this->redirectDonorFailed($payment, 'api_unavailable');
        }

        $payment->update([
            'status' => Payment::STATUS_PENDING,
            'external_payment_id' => (string) $result['invoice_id'],
            'notes' => array_merge($payment->notes ?? [], $result['raw_response']),
        ]);

        $this->auditService->log('payment', 'gateway_initiated', [
            'payment_id' => $payment->id,
            'invoice_id' => $result['invoice_id'],
        ], $payment->sponsor_id);

        return redirect()->away($result['payment_url']);
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        $paymentId = $request->query('paymentId') ?? $request->query('invoiceId');

        if (! $paymentId) {
            $this->auditService->log('payment', 'callback_received', [
                'error' => 'missing_payment_id',
                'query' => $request->query(),
            ], null);

            Log::critical('Payment critical failure: missing payment ID in callback', [
                'query' => $request->query(),
            ]);

            return $this->redirectDonorFailed(null, 'missing_callback');
        }

        $paymentIdStr = (string) $paymentId;

        try {
            $keyType = $request->query('paymentId') ? 'PaymentId' : 'InvoiceId';
            $statusResult = $this->myFatoorah->getPaymentStatus($paymentIdStr, $keyType);
        } catch (\Throwable $e) {
            $this->auditService->log('payment', 'callback_verification_failed', [
                'error' => $e->getMessage(),
                'external_id' => $paymentIdStr,
            ], null);

            Log::critical('Payment critical failure: callback verification failed', [
                'external_id' => $paymentIdStr,
                'error' => $e->getMessage(),
            ]);

            $payment = $this->findPaymentByExternalIds($paymentIdStr);

            return $this->redirectDonorFailed($payment, 'api_unavailable');
        }

        $normalized = $this->normalizeGatewayStatusResult($statusResult, $paymentIdStr);
        if ($normalized === null) {
            $this->auditService->log('payment', 'callback_unexpected_response', [
                'external_id' => $paymentIdStr,
                'raw_type' => get_debug_type($statusResult),
            ], null);

            Log::critical('Payment critical failure: unexpected gateway response shape', [
                'external_id' => $paymentIdStr,
            ]);

            $payment = $this->findPaymentByExternalIds($paymentIdStr);

            return $this->redirectDonorFailed($payment, 'ambiguous');
        }

        $invoiceId = $normalized['invoice_id'];
        $gatewayStatus = $normalized['status'];

        try {
            return $this->processCallbackPaymentState($invoiceId, $gatewayStatus);
        } catch (\Throwable $e) {
            Log::critical('Payment critical failure: callback processing failed', [
                'invoice_id' => $invoiceId,
                'gateway_status' => $gatewayStatus,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            $this->auditService->log('payment', 'callback_processing_failed', [
                'invoice_id' => $invoiceId,
                'gateway_status' => $gatewayStatus,
                'error' => $e->getMessage(),
            ], null);

            $payment = Payment::where('external_payment_id', $invoiceId)->first();

            return $this->redirectDonorFailed($payment, 'processing_error');
        }
    }

    /**
     * Core callback logic after gateway returned a normalized status (wrapped in try/catch by handleCallback).
     *
     * Race-condition safety: uses pessimistic locking (SELECT ... FOR UPDATE) on the Payment row
     * so that concurrent callbacks for the same invoice are serialised. The idempotency guards
     * (status check + FundTransaction existence check) now run inside the same DB transaction
     * that credits the wallet, eliminating the window where two requests could both pass the
     * guard and create duplicate FundTransactions.
     */
    private function processCallbackPaymentState(string $invoiceId, string $gatewayStatus): RedirectResponse
    {
        // ── 1. Locate the payment ──────────────────────────────────────────
        $payment = Payment::where('external_payment_id', $invoiceId)->first();

        if (! $payment) {
            $this->auditService->log('payment', 'callback_payment_not_found', [
                'invoice_id' => $invoiceId,
            ], null);

            Log::critical('Payment critical failure: payment not found for callback', [
                'invoice_id' => $invoiceId,
            ]);

            return $this->redirectDonorFailed(null, 'payment_not_found', true);
        }

        // ── 2. Quick idempotency check (no lock needed — read-only fast path) ─
        if ($payment->status === Payment::STATUS_SUCCEEDED) {
            return $this->redirectPaymentSuccess($payment);
        }

        // ── 3. Gateway says "Paid" → credit funds inside a locked transaction ─
        $status = $gatewayStatus;

        if (in_array($status, ['Paid', 'DuplicatePayment'], true)) {
            return DB::transaction(function () use ($payment) {

                // Re-fetch with pessimistic lock to serialise concurrent callbacks
                $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();

                // Double-check after acquiring lock (another request may have finished first)
                if ($locked->status === Payment::STATUS_SUCCEEDED) {
                    return $this->redirectPaymentSuccess($locked);
                }

                if (FundTransaction::where('payment_id', $locked->id)->exists()) {
                    $locked->update(['status' => Payment::STATUS_SUCCEEDED]);

                    return $this->redirectPaymentSuccess($locked);
                }

                $locked->update([
                    'status' => Payment::STATUS_SUCCEEDED,
                    'notes' => array_merge($locked->notes ?? [], ['callback_verified' => true]),
                ]);

                $this->systemWallet->addFundsFromDonation(
                    (float) $locked->amount,
                    $locked->sponsor_id,
                    $locked->id
                );

                Cache::forget('top_donors_list');

                if (! $locked->is_guest) {
                    $this->notificationService->sendDonationReceipt($locked);
                }

                $this->notificationService->sendDonationSuccessToAdmins($locked);

                $this->auditService->log('payment', 'succeeded', [
                    'payment_id' => $locked->id,
                    'amount' => $locked->amount,
                    'is_guest' => $locked->is_guest,
                ], $locked->sponsor_id);

                return $this->redirectPaymentSuccess($locked);
            });
        }

        // ── 4. Gateway returned a non-success status ─────────────────────
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'notes' => array_merge($payment->notes ?? [], ['callback_status' => $status]),
        ]);

        $this->auditService->log('payment', 'failed', [
            'payment_id' => $payment->id,
            'status' => $status,
        ], $payment->sponsor_id);

        Log::critical('Payment critical failure: payment did not succeed', [
            'payment_id' => $payment->id,
            'sponsor_id' => $payment->sponsor_id,
            'amount' => $payment->amount,
            'gateway_status' => $status,
        ]);

        $reason = 'gateway_declined';
        if ($status === '' || $status === 'Unknown') {
            $reason = 'ambiguous';
        }

        return $this->redirectDonorFailed($payment, $reason);
    }

    public function handleError(Request $request): RedirectResponse
    {
        $paymentId = $request->query('paymentId') ?? $request->query('invoiceId');

        $this->auditService->log('payment', 'error_url_received', [
            'external_id' => $paymentId,
            'query' => $request->query(),
        ], null);

        if (! $paymentId) {
            return $this->redirectDonorFailed(null, 'api_unavailable');
        }

        $paymentIdStr = (string) $paymentId;
        $payment = null;

        try {
            $keyType = $request->query('paymentId') ? 'PaymentId' : 'InvoiceId';
            $statusResult = $this->myFatoorah->getPaymentStatus($paymentIdStr, $keyType);
            $normalized = $this->normalizeGatewayStatusResult($statusResult, $paymentIdStr);

            if ($normalized === null) {
                Log::warning('Payment error URL: unexpected gateway response', [
                    'external_id' => $paymentIdStr,
                ]);
                $payment = $this->findPaymentByExternalIds($paymentIdStr);
            } else {
                $payment = Payment::where('external_payment_id', $normalized['invoice_id'])->first();
            }

            if ($payment && $normalized !== null && in_array($normalized['status'], ['Paid', 'DuplicatePayment'], true)) {
                try {
                    return $this->processCallbackPaymentState($normalized['invoice_id'], $normalized['status']);
                } catch (\Throwable $e) {
                    Log::critical('Payment critical failure: error URL paid-state processing failed', [
                        'invoice_id' => $normalized['invoice_id'],
                        'gateway_status' => $normalized['status'],
                        'error' => $e->getMessage(),
                        'exception' => $e::class,
                    ]);

                    $this->auditService->log('payment', 'callback_processing_failed', [
                        'invoice_id' => $normalized['invoice_id'],
                        'gateway_status' => $normalized['status'],
                        'source' => 'error_url',
                        'error' => $e->getMessage(),
                    ], null);

                    return $this->redirectDonorFailed($payment->fresh(), 'processing_error');
                }
            }

            if ($payment && $payment->status !== Payment::STATUS_SUCCEEDED) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'notes' => array_merge($payment->notes ?? [], [
                        'error_url' => true,
                        'callback_status' => $normalized !== null ? $normalized['status'] : null,
                    ]),
                ]);

                $this->auditService->log('payment', 'failed', [
                    'payment_id' => $payment->id,
                    'source' => 'error_url',
                ], $payment->sponsor_id);

                Log::critical('Payment critical failure: error URL received', [
                    'payment_id' => $payment->id,
                    'sponsor_id' => $payment->sponsor_id,
                    'amount' => $payment->amount,
                ]);
            }
        } catch (\Throwable $e) {
            $this->auditService->log('payment', 'error_url_verification_failed', [
                'external_id' => $paymentIdStr,
                'error' => $e->getMessage(),
            ], null);

            Log::warning('Payment error URL: gateway verification failed', [
                'external_id' => $paymentIdStr,
                'error' => $e->getMessage(),
            ]);

            $payment = $this->findPaymentByExternalIds($paymentIdStr);

            if ($payment && $payment->status !== Payment::STATUS_SUCCEEDED) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'notes' => array_merge($payment->notes ?? [], [
                        'error_url' => true,
                        'gateway_unavailable' => true,
                    ]),
                ]);

                $this->auditService->log('payment', 'failed', [
                    'payment_id' => $payment->id,
                    'source' => 'error_url',
                    'unverified' => true,
                ], $payment->sponsor_id);
            }
        }

        return $this->redirectDonorFailed($payment, 'api_unavailable');
    }

    /**
     * @return array{invoice_id: string, status: string}|null
     */
    private function normalizeGatewayStatusResult(mixed $statusResult, string $fallbackExternalId): ?array
    {
        if (! is_array($statusResult)) {
            return null;
        }

        $status = $statusResult['status'] ?? 'Unknown';
        if (! is_string($status)) {
            $status = is_scalar($status) ? (string) $status : 'Unknown';
        }

        $invoiceId = $statusResult['invoice_id'] ?? null;
        if ($invoiceId !== null && ! is_scalar($invoiceId)) {
            $invoiceId = null;
        }

        $invoiceIdStr = $invoiceId !== null ? (string) $invoiceId : $fallbackExternalId;

        if ($invoiceIdStr === '') {
            return null;
        }

        return [
            'invoice_id' => $invoiceIdStr,
            'status' => $status,
        ];
    }

    /**
     * Lookup by stored invoice id (same value MyFatoorah sends as invoiceId in many flows).
     */
    private function findPaymentByExternalIds(string $externalId): ?Payment
    {
        return Payment::where('external_payment_id', $externalId)->first();
    }

    private function redirectPaymentSuccess(Payment $payment): RedirectResponse
    {
        if ($payment->is_guest) {
            return redirect()->route('guest.donation.success', ['token' => $payment->idempotency_key]);
        }

        return redirect()->route('donor.payments.success', ['payment_id' => $payment->id]);
    }

    private function redirectDonorFailed(?Payment $payment, string $reason, bool $guestFallback = false): RedirectResponse
    {
        $params = [];
        if ($payment !== null) {
            $params[$payment->is_guest ? 'token' : 'payment_id'] = $payment->is_guest
                ? $payment->idempotency_key
                : $payment->id;
        }

        $route = ($payment?->is_guest || ($payment === null && $guestFallback))
            ? 'guest.donation.failed'
            : 'donor.payments.failed';

        return redirect()->route($route, $params)
            ->with('payment_reason', $reason);
    }
}
