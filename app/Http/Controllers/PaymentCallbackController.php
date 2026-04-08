<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function callback(Request $request)
    {
        return $this->paymentService->handleCallback($request);
    }

    public function error(Request $request)
    {
        return $this->paymentService->handleError($request);
    }

    public function success(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment = null;

        if ($paymentId && auth()->check()) {
            $payment = Payment::where('id', $paymentId)
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->first();

            if ($payment && $payment->sponsor_id !== (int) auth()->id()) {
                abort(403, __('You do not have access to this page.'));
            }
        }

        return view('donor.payments.success', compact('payment'));
    }

    public function failed(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment = null;

        if ($paymentId && auth()->check()) {
            $payment = Payment::find($paymentId);

            if ($payment && $payment->sponsor_id !== (int) auth()->id()) {
                abort(403, __('You do not have access to this page.'));
            }
        }

        return view('donor.payments.failed', compact('payment'));
    }
}
