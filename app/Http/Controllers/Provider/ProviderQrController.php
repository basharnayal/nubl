<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Models\ProviderProfile;
use App\Models\Ewallet;
use App\Http\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class ProviderQrController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    public function scan()
    {
        return view('provider.qr.scan');
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $rawToken = $request->input('token');
        $providerId = auth()->id();

        // Retry requirement (FR-9.3): max 3 attempts per provider+token within 30 seconds
        $cacheKey = "qr_redeem_attempts:{$providerId}:" . md5($rawToken);
        if (RateLimiter::tooManyAttempts($cacheKey, 3)) {
            return response()->json([
                'error' => __('Too many attempts, wait 30 seconds.')
            ], 429);
        }
        RateLimiter::hit($cacheKey, 30);

        $tokenHash = hash('sha256', $rawToken);

        try {
            DB::beginTransaction();

            // Atomic validation & locks
            $redemption = OrderRedemption::where('token_code', $tokenHash)->lockForUpdate()->first();

            if (!$redemption) {
                // Return generic error but clearly distinguish invalid
                return response()->json(['error' => __('Invalid token.')], 422);
            }

            if ($redemption->provider_id !== $providerId) {
                return response()->json(['error' => __('This code is not valid for your account.')], 403);
            }

            if ($redemption->status === 'REDEEMED') {
                return response()->json(['error' => __('This code has already been used.')], 422);
            }

            if ($redemption->status === 'EXPIRED' || $redemption->redeem_expires_at->isPast()) {
                return response()->json(['error' => __('This QR code has expired.')], 422);
            }

            if ($redemption->status !== 'PENDING') {
                return response()->json(['error' => __('This code cannot be redeemed.')], 422);
            }

            // Perform Redemption
            $redemption->status = 'REDEEMED';
            $redemption->save();

            // Link fund transactions if CITY_FUND (or just transfer if not already transferred)
            $requestModel = $redemption->request;

            // Only transfer if it is CITY_FUND and we haven't already. 
            // In the legacy code, approval might have transferred it, but FR-11.1 requires us to create fund_transactions with order_redemption_id
            $existingFunds = FundTransaction::where('order_redemption_id', $redemption->id)->exists();
            if (!$existingFunds && $requestModel->funding_source === 'CITY_FUND') {
                $amount = (float) $requestModel->reserved_amount;

                $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->first();
                $providerProfile = ProviderProfile::where('user_id', $providerId)->first();

                if ($systemWallet && $providerProfile) {
                    $providerWallet = $providerProfile->ewallet ?? $providerProfile->ewallet()->create([
                        'owner_type' => 'PROVIDER',
                        'balance' => 0,
                        'status' => true,
                    ]);

                    FundTransaction::create([
                        'wallet_id' => $systemWallet->id,
                        'sponsor_id' => null,
                        'source' => FundTransaction::SOURCE_PAYOUT ?? 'PAYOUT',
                        'amount' => $amount,
                        'direction' => FundTransaction::DIRECTION_OUT ?? 'OUT',
                        'payment_id' => null,
                        'request_id' => $requestModel->id,
                        'order_redemption_id' => $redemption->id,
                    ]);

                    FundTransaction::create([
                        'wallet_id' => $providerWallet->id,
                        'sponsor_id' => null,
                        'source' => FundTransaction::SOURCE_PAYOUT ?? 'PAYOUT',
                        'amount' => $amount,
                        'direction' => FundTransaction::DIRECTION_IN ?? 'IN',
                        'payment_id' => null,
                        'request_id' => $requestModel->id,
                        'order_redemption_id' => $redemption->id,
                    ]);
                }
            }

            // Audit logging
            $this->auditService->log('redemption', 'redeemed', [
                'redemption_id' => $redemption->id,
                'request_id' => $requestModel->id,
                'provider_id' => $providerId,
            ]);

            DB::commit();

            RateLimiter::clear($cacheKey);

            return response()->json([
                'success' => __('Successfully redeemed!'),
                'order_redemption_id' => $redemption->id,
                'redirect_url' => route('provider.proof.index', $redemption->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => __('A server error occurred during redemption.')], 500);
        }
    }
}
