<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index()
    {
        $payments = Payment::where('sponsor_id', auth()->id())
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->with('requestPaymentLinks.request')
            ->latest()
            ->paginate(15);

        return view('donor.donations.index', compact('payments'));
    }

    public function receipt(Payment $payment)
    {
        if ($payment->sponsor_id !== auth()->id()) {
            abort(403);
        }
        if ($payment->status !== Payment::STATUS_SUCCEEDED) {
            abort(404);
        }

        return view('donor.donations.receipt', compact('payment'));
    }

    public function create()
    {
        return view('donor.donations.new');
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
        ], [
            'amount.min' => __('The minimum donation amount is 1 SAR.'),
        ]);

        $payment = $this->paymentService->initiateSponsorPayment(
            auth()->id(),
            (float) $validated['amount']
        );

        return $this->paymentService->redirectToGateway($payment);
    }
}
