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
     * Only executes if the request is REDEEMABLE and doesn't already have one.
     */
    public static function generateForRequest(RequestModel $request): ?OrderRedemption
    {
        if ($request->status !== 'REDEEMABLE') {
            return null;
        }

        // Idempotency constraint: do not duplicate tokens for the same request
        $existing = $request->redemption;
        if ($existing) {
            return $existing;
        }

        // Generate a 32-character secure random QR token
        $rawToken = Str::random(32);

        // Generate a 9-character human-typable token combining letters and numbers
        $shortToken = strtoupper(Str::random(9));

        // Hash for strict lookup constraints
        $tokenCode = hash('sha256', $rawToken);
        $shortCodeHash = hash('sha256', $shortToken);

        // Encrypt safe copies
        $tokenCiphertext = Crypt::encryptString($rawToken);
        $shortCodeCiphertext = Crypt::encryptString($shortToken);

        // Define TTL
        $ttlMinutes = config('qr.ttl_minutes', 180);

        return $request->redemption()->create([
            'provider_id' => $request->provider_id,
            'token_code' => $tokenCode,
            'short_code_hash' => $shortCodeHash,
            'token_ciphertext' => $tokenCiphertext,
            'short_code_ciphertext' => $shortCodeCiphertext,
            'ttl_minutes' => $ttlMinutes,
            'redeem_expires_at' => now()->addMinutes($ttlMinutes),
            'status' => 'PENDING',
        ]);
    }
}
