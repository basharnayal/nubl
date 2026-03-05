<?php

namespace App\Http\Services;

use App\Models\OrderRedemption;
use App\Models\Request as RequestModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class RedemptionService
{
    /**
     * Idempotently generate a redemption token for a request.
     * Only executes if the request is APPROVED or REDEEMABLE and doesn't already have one.
     */
    public static function generateForRequest(RequestModel $request): ?OrderRedemption
    {
        if (!in_array($request->status, ['APPROVED', 'REDEEMABLE'])) {
            return null;
        }

        // Idempotency constraint: do not duplicate tokens for the same request
        $existing = $request->redemption;
        if ($existing) {
            return $existing;
        }

        // Generate a 9-character human-typable token combining letters and numbers
        $rawToken = strtoupper(Str::random(9));

        // Hash for strict lookup constraint
        $tokenCode = hash('sha256', $rawToken);

        // Encrypt for retrieving and displaying securely later
        $tokenCiphertext = Crypt::encryptString($rawToken);

        // Define TTL
        $ttlMinutes = config('qr.ttl_minutes', 180);

        return $request->redemption()->create([
            'provider_id' => $request->provider_id,
            'token_code' => $tokenCode,
            'token_ciphertext' => $tokenCiphertext,
            'ttl_minutes' => $ttlMinutes,
            'redeem_expires_at' => now()->addMinutes($ttlMinutes),
            'status' => 'PENDING',
        ]);
    }
}
