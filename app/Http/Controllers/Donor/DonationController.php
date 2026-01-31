<?php
// Fake Example

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Http\Services\DonationService;
use App\Http\Requests\StoreDonationRequest;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function __construct(
        private DonationService $donationService
    ) {}
    
    public function create()
    {
        return view('donor.donations.create');
    }
    
    public function store(StoreDonationRequest $request)
    {
        try {
            $result = $this->donationService->initiateDonation(
                auth()->id(),
                $request->validated()['amount']
            );
            
            return redirect($result['payment_url']);
        } catch (\Exception $e) {
            return $this->redirectWithError(
                'donor.donations.create',
                'Failed to initiate donation: ' . $e->getMessage()
            );
        }
    }
    
    public function callback(Request $request)
    {
        try {
            $donation = $this->donationService->confirmDonation(
                $request->input('payment_reference')
            );
            
            return $this->redirectWithSuccess(
                'donor.donations.show',
                'Donation confirmed successfully!'
            );
        } catch (\Exception $e) {
            return $this->redirectWithError(
                'donor.dashboard',
                'Payment verification failed'
            );
        }
    }
}