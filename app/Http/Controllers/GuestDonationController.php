<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class GuestDonationController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
        ], [
            'amount.min' => __('The minimum donation amount is 1 SAR.'),
        ]);

        $payment = $this->paymentService->initiateGuestPayment(
            (float) $validated['amount']
        );

        return $this->paymentService->redirectToGateway($payment);
    }

    public function success(Request $request)
    {
        $token = $request->query('token');
        $payment = null;

        if ($token) {
            $payment = Payment::where('idempotency_key', $token)
                ->where('is_guest', true)
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->first();
        }

        return view('guest-donation.success', compact('payment'));
    }

    public function receipt(string $token)
    {
        $payment = Payment::where('idempotency_key', $token)
            ->where('is_guest', true)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->firstOrFail();

        return view('guest-donation.receipt', compact('payment'));
    }

    public function failed(Request $request)
    {
        $token = $request->query('token');
        $payment = null;

        if ($token) {
            $payment = Payment::where('idempotency_key', $token)
                ->where('is_guest', true)
                ->first();
        }

        return view('guest-donation.failed', compact('payment'));
    }
}
