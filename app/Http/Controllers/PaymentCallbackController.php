<?php

namespace App\Http\Controllers;

use App\Http\Services\PaymentService;
use App\Models\Payment;
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
        $payment = $paymentId ? Payment::find($paymentId) : null;

        return view('donor.payments.success', compact('payment'));
    }

    public function failed(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment = $paymentId ? Payment::find($paymentId) : null;

        return view('donor.payments.failed', compact('payment'));
    }
}
