<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Http\Services\AuditService;
use App\Http\Services\SystemWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class ProviderQrController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private SystemWalletService $systemWalletService
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
            $redemption = OrderRedemption::where(function ($query) use ($tokenHash) {
                $query->where('token_code', $tokenHash)
                    ->orWhere('short_code_hash', $tokenHash);
            })->lockForUpdate()->first();

            if (!$redemption) {
                return response()->json(['error' => __('Invalid token.')], 404);
            }

            if ($redemption->provider_id !== $providerId) {
                return response()->json(['error' => __('This code is not valid for your account.')], 403);
            }

            if ($redemption->status === 'REDEEMED') {
                return response()->json(['error' => __('This code has already been used.')], 409);
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

            // Transfer from city fund to provider at redemption (QR scan), not at approval
            $requestModel = $redemption->request;

            $alreadyTransferred = FundTransaction::where('request_id', $requestModel->id)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->exists();

            if (!$alreadyTransferred && $requestModel->funding_source === 'CITY_FUND') {
                $this->systemWalletService->transferToProviderForRequest($requestModel, $redemption->id);
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

        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Redemption error: ' . $e->getMessage(), ['exception' => $e]);

            // Expose the real error safely to diagnose the silent 500
            $errorMsg = $e instanceof \RuntimeException || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? $e->getMessage()
                : __('A server error occurred during redemption.') . ' : ' . $e->getMessage();

            return response()->json(['error' => $errorMsg], 500);
        }
    }
}
