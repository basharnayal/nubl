<?php

namespace App\Http\Services;

use App\Models\FundTransaction;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private MyFatoorahService $myFatoorah,
        private SystemWalletService $systemWallet,
        private AuditService $auditService
    ) {}

    public function initiateSponsorPayment(int $sponsorId, float $amount, ?string $idempotencyKey = null): Payment
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
        ]);

        $this->auditService->log('payment', 'initiated', [
            'payment_id' => $payment->id,
            'amount' => $amount,
            'sponsor_id' => $sponsorId,
        ], $sponsorId);

        return $payment;
    }

    public function redirectToGateway(Payment $payment): RedirectResponse
    {
        $callbackUrl = route('payments.callback');
        $errorUrl = route('payments.error');
        $user = $payment->sponsor;

        $result = $this->myFatoorah->createInvoice(
            (float) $payment->amount,
            (string) $payment->id,
            $callbackUrl,
            $errorUrl,
            $user?->email,
            $user?->name
        );

        $payment->update([
            'status' => Payment::STATUS_PENDING,
            'external_payment_id' => (string) $result['invoice_id'],
            'notes' => $result['raw_response'],
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

            return redirect()->route('donor.payments.failed');
        }

        $this->auditService->log('payment', 'callback_received', [
            'external_id' => $paymentId,
        ], null);

        try {
            $keyType = $request->query('paymentId') ? 'PaymentId' : 'InvoiceId';
            $statusResult = $this->myFatoorah->getPaymentStatus((string) $paymentId, $keyType);
        } catch (\Throwable $e) {
            $this->auditService->log('payment', 'callback_verification_failed', [
                'error' => $e->getMessage(),
                'external_id' => $paymentId,
            ], null);

            return redirect()->route('donor.payments.failed');
        }

        $invoiceId = (string) ($statusResult['invoice_id'] ?? $paymentId);
        $payment = Payment::where('external_payment_id', $invoiceId)->first();

        if (! $payment) {
            $this->auditService->log('payment', 'callback_payment_not_found', [
                'invoice_id' => $invoiceId,
            ], null);

            return redirect()->route('donor.payments.failed');
        }

        // Idempotency: already succeeded — do not process again
        if ($payment->status === Payment::STATUS_SUCCEEDED) {
            return redirect()->route('donor.payments.success', ['payment_id' => $payment->id]);
        }

        // Idempotency: fund_transaction already exists — do not credit again
        if (FundTransaction::where('payment_id', $payment->id)->exists()) {
            $payment->update(['status' => Payment::STATUS_SUCCEEDED]);

            return redirect()->route('donor.payments.success', ['payment_id' => $payment->id]);
        }

        $status = $statusResult['status'] ?? '';

        if (in_array($status, ['Paid', 'DuplicatePayment'])) {
            return DB::transaction(function () use ($payment) {
                $payment->update([
                    'status' => Payment::STATUS_SUCCEEDED,
                    'notes' => array_merge($payment->notes ?? [], ['callback_verified' => true]),
                ]);

                $this->systemWallet->addFundsFromDonation(
                    (float) $payment->amount,
                    $payment->sponsor_id,
                    $payment->id
                );

                $this->auditService->log('payment', 'succeeded', [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                ], $payment->sponsor_id);

                return redirect()->route('donor.payments.success', ['payment_id' => $payment->id]);
            });
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'notes' => array_merge($payment->notes ?? [], ['callback_status' => $status]),
        ]);

        $this->auditService->log('payment', 'failed', [
            'payment_id' => $payment->id,
            'status' => $status,
        ], $payment->sponsor_id);

        return redirect()->route('donor.payments.failed', ['payment_id' => $payment->id]);
    }

    public function handleError(Request $request): RedirectResponse
    {
        $paymentId = $request->query('paymentId') ?? $request->query('invoiceId');
        $payment = null;

        $this->auditService->log('payment', 'error_url_received', [
            'external_id' => $paymentId,
            'query' => $request->query(),
        ], null);

        if ($paymentId) {
            try {
                $keyType = $request->query('paymentId') ? 'PaymentId' : 'InvoiceId';
                $statusResult = $this->myFatoorah->getPaymentStatus((string) $paymentId, $keyType);
                $invoiceId = (string) ($statusResult['invoice_id'] ?? $paymentId);
                $payment = Payment::where('external_payment_id', $invoiceId)->first();

                if ($payment && $payment->status !== Payment::STATUS_SUCCEEDED) {
                    $payment->update([
                        'status' => Payment::STATUS_FAILED,
                        'notes' => array_merge($payment->notes ?? [], ['error_url' => true]),
                    ]);

                    $this->auditService->log('payment', 'failed', [
                        'payment_id' => $payment->id,
                        'source' => 'error_url',
                    ], $payment->sponsor_id);
                }
            } catch (\Throwable $e) {
                // Ignore — payment may not exist
            }
        }

        return redirect()->route('donor.payments.failed', isset($payment) ? ['payment_id' => $payment->id] : []);
    }
}
