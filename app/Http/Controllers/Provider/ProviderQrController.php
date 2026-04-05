<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\RedeemProviderQrRequest;
use App\Http\Services\RedemptionService;
use Illuminate\Support\Facades\RateLimiter;

class ProviderQrController extends Controller
{
    public function __construct(
        private RedemptionService $redemptionService
    ) {}

    public function scan()
    {
        return view('provider.qr.scan');
    }

    public function redeem(RedeemProviderQrRequest $request)
    {
        $rawToken = $request->input('token');
        $providerId = auth()->id();

        // FR-9.3: max 2 redemption attempts per provider+token within 30 seconds (SRS: two attempts)
        $cacheKey = "qr_redeem_attempts:{$providerId}:".md5($rawToken);
        if (RateLimiter::tooManyAttempts($cacheKey, 2)) {
            return response()->json([
                'error' => __('Too many attempts, wait 30 seconds.'),
            ], 429);
        }
        RateLimiter::hit($cacheKey, 30);

        $tokenHash = hash('sha256', $rawToken);

        $result = $this->redemptionService->redeem($tokenHash, $providerId);

        if (! empty($result['clear_rate_limit'])) {
            RateLimiter::clear($cacheKey);
        }

        return response()->json($result['body'], $result['status']);
    }
}
