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
        $paymentId = $request->query('payment_id');
        $payment = null;

        if ($paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('is_guest', true)
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->first();
        }

        return view('guest-donation.success', compact('payment'));
    }

    public function receipt(Payment $payment)
    {
        if (! $payment->is_guest || $payment->status !== Payment::STATUS_SUCCEEDED) {
            abort(404);
        }

        return view('guest-donation.receipt', compact('payment'));
    }

    public function failed(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment = null;

        if ($paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('is_guest', true)
                ->first();
        }

        return view('guest-donation.failed', compact('payment'));
    }
}
