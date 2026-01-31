<?php
// Fake Example

namespace App\Http\Services;

use App\Models\Donation;
use App\Models\CityFund;
use App\Events\DonationReceived;
use App\Http\Services\MyFatoorahService;
use Illuminate\Support\Facades\DB;

class DonationService
{
    public function __construct(
        private MyFatoorahService $myFatoorah,
        private AuditService $auditService
    ) {}
    
    public function initiateDonation(int $userId, float $amount): array
    {
        return DB::transaction(function () use ($userId, $amount) {
            // Create payment link via MyFatoorah
            $paymentLink = $this->myFatoorah->createPaymentLink([
                'amount' => $amount,
                'user_id' => $userId,
            ]);
            
            // Create pending donation record
            $donation = Donation::create([
                'user_id' => $userId,
                'amount' => $amount,
                'status' => DonationStatus::PENDING,
                'payment_reference' => $paymentLink['reference'],
            ]);
            
            return [
                'donation' => $donation,
                'payment_url' => $paymentLink['url'],
            ];
        });
    }
    
    public function confirmDonation(string $paymentReference): Donation
    {
        return DB::transaction(function () use ($paymentReference) {
            // Verify with MyFatoorah
            $verification = $this->myFatoorah->verifyPayment($paymentReference);
            
            if (!$verification['success']) {
                throw new \Exception('Payment verification failed');
            }
            
            $donation = Donation::where('payment_reference', $paymentReference)->firstOrFail();
            $donation->update([
                'status' => DonationStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);
            
            // Update city fund
            CityFund::first()->increment('balance', $donation->amount);
            
            // Audit log
            $this->auditService->log('donation', 'confirmed', [
                'donation_id' => $donation->id,
                'amount' => $donation->amount,
            ], $donation->user_id);
            
            // Fire event
            event(new DonationReceived($donation));
            
            return $donation;
        });
    }
}