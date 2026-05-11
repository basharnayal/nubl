<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\OrderProof;
use App\Models\OrderRedemption;
use App\Models\ProviderProfile;
use App\Models\Request;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoRedemptionSeeder extends Seeder
{
    public function run(): void
    {
        if (OrderRedemption::whereHas('request', fn ($q) => $q->where('invoice_id', 'like', 'DEMO-%'))->exists()) {
            $this->command->info('⏭ Demo redemptions already seeded.');

            return;
        }

        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->first();

        // Requests that need redemption tokens
        $redeemableRequests = Request::where('invoice_id', 'like', 'DEMO-%')
            ->whereIn('status', ['APPROVED', 'REDEEMABLE', 'FULFILLED'])
            ->get();

        $redemptionCount = 0;
        $proofCount = 0;
        $txnCount = 0;

        foreach ($redeemableRequests as $req) {
            $tokenRaw = strtoupper(Str::random(9));
            $tokenHash = hash('sha256', $tokenRaw);
            $shortCode = substr($tokenRaw, 0, 6);
            $shortHash = hash('sha256', $shortCode);
            $ttl = 180;
            $expiresAt = $req->created_at->copy()->addMinutes($ttl);

            // Determine redemption status based on request status
            $redemptionStatus = match ($req->status) {
                'FULFILLED' => 'REDEEMED',
                'APPROVED', 'REDEEMABLE' => (rand(1, 10) <= 2) ? 'EXPIRED' : 'PENDING',
                default => 'PENDING',
            };

            // If EXPIRED, set expiry in the past
            if ($redemptionStatus === 'EXPIRED') {
                $expiresAt = $req->created_at->copy()->subHours(rand(1, 24));
            }

            $redemption = OrderRedemption::create([
                'request_id' => $req->id,
                'provider_id' => $req->provider_id,
                'token_code' => $tokenHash,
                'short_code_hash' => $shortHash,
                'token_ciphertext' => $tokenRaw, // Will be encrypted by cast
                'short_code_ciphertext' => $shortCode,
                'token_qr_url' => null,
                'ttl_minutes' => $ttl,
                'redeem_expires_at' => $expiresAt,
                'status' => $redemptionStatus,
                'created_at' => $req->created_at,
                'updated_at' => $redemptionStatus === 'REDEEMED'
                    ? $req->created_at->copy()->addHours(rand(1, 48))
                    : $req->created_at,
            ]);
            $redemptionCount++;

            // FULFILLED requests: create proof + fund transactions
            if ($req->status === 'FULFILLED' && $redemptionStatus === 'REDEEMED') {
                $fulfilledAt = $redemption->updated_at->copy()->addMinutes(rand(5, 60));

                OrderProof::create([
                    'order_redemption_id' => $redemption->id,
                    'proof_url' => 'private/proofs/'.$redemption->id.'/demo_proof.jpg',
                    'is_provider_donation' => $req->funding_source === 'PROVIDER_ADOPTION',
                    'fulfilled_at' => $fulfilledAt,
                    'created_at' => $fulfilledAt,
                    'updated_at' => $fulfilledAt,
                ]);
                $proofCount++;

                // For CITY_FUND fulfilled requests: system wallet → provider wallet transfer
                if ($req->funding_source === 'CITY_FUND' && $systemWallet) {
                    $providerProfile = ProviderProfile::where('user_id', $req->provider_id)->first();
                    $providerWallet = $providerProfile
                        ? Ewallet::where('owner_type', 'PROVIDER')->where('owner_id', $providerProfile->id)->first()
                        : null;

                    if ($providerWallet) {
                        $txnTime = $fulfilledAt;

                        // OUT from system wallet
                        FundTransaction::create([
                            'wallet_id' => $systemWallet->id,
                            'source' => FundTransaction::SOURCE_REDEMPTION,
                            'amount' => $req->reserved_amount,
                            'direction' => FundTransaction::DIRECTION_OUT,
                            'request_id' => $req->id,
                            'order_redemption_id' => $redemption->id,
                            'created_at' => $txnTime,
                            'updated_at' => $txnTime,
                        ]);

                        // IN to provider wallet
                        FundTransaction::create([
                            'wallet_id' => $providerWallet->id,
                            'source' => FundTransaction::SOURCE_REDEMPTION,
                            'amount' => $req->reserved_amount,
                            'direction' => FundTransaction::DIRECTION_IN,
                            'request_id' => $req->id,
                            'order_redemption_id' => $redemption->id,
                            'created_at' => $txnTime,
                            'updated_at' => $txnTime,
                        ]);

                        $txnCount += 2;
                    }
                }
            }
        }

        $this->command->info("✓ Seeded {$redemptionCount} order redemptions, {$proofCount} order proofs, {$txnCount} fund transactions");
    }
}
