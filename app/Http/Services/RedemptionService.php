<?php

namespace App\Http\Services;

use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Models\Request as RequestModel;
use App\Support\QrTtl;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RedemptionService
{
    public function __construct(
        private AuditService $auditService,
        private SystemWalletService $systemWalletService
    ) {}

    /**
     * Redeem a QR token for a provider (transactional: lock, validate, payout, audit).
     *
     * @return array{status: int, body: array<string, mixed>, clear_rate_limit?: bool}
     */
    public function redeem(string $tokenHash, int $providerId): array
    {
        try {
            DB::beginTransaction();

            $redemption = OrderRedemption::where(function ($query) use ($tokenHash) {
                $query->where('token_code', $tokenHash)
                    ->orWhere('short_code_hash', $tokenHash);
            })->lockForUpdate()->first();

            if (! $redemption) {
                DB::rollBack();

                return ['status' => 404, 'body' => ['error' => __('Invalid token.')]];
            }

            if ($redemption->provider_id !== $providerId) {
                DB::rollBack();

                return ['status' => 403, 'body' => ['error' => __('This code is not valid for your account.')]];
            }

            if ($redemption->status === 'REDEEMED') {
                DB::rollBack();

                return ['status' => 409, 'body' => ['error' => __('This code has already been used.')]];
            }

            if ($redemption->status === 'EXPIRED' || $redemption->redeem_expires_at->isPast()) {
                DB::rollBack();

                return ['status' => 422, 'body' => ['error' => __('This QR code has expired.')]];
            }

            if ($redemption->status !== 'PENDING') {
                DB::rollBack();

                return ['status' => 422, 'body' => ['error' => __('This code cannot be redeemed.')]];
            }

            $redemption->status = 'REDEEMED';
            $redemption->save();

            $requestModel = $redemption->request;

            $alreadyTransferred = FundTransaction::where('request_id', $requestModel->id)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->exists();

            if (! $alreadyTransferred && $requestModel->funding_source === 'CITY_FUND') {
                $this->systemWalletService->transferToProviderForRequest($requestModel, $redemption->id);
            }

            $this->auditService->log('redemption', 'redeemed', [
                'redemption_id' => $redemption->id,
                'request_id' => $requestModel->id,
                'provider_id' => $providerId,
            ]);

            DB::commit();

            return [
                'status' => 200,
                'clear_rate_limit' => true,
                'body' => [
                    'success' => __('Successfully redeemed!'),
                    'order_redemption_id' => $redemption->id,
                    'redirect_url' => route('provider.proof.index', $redemption->id),
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Redemption error: '.$e->getMessage(), ['exception' => $e]);

            $errorMsg = $e instanceof \RuntimeException || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? $e->getMessage()
                : __('A server error occurred during redemption.').' : '.$e->getMessage();

            return ['status' => 500, 'body' => ['error' => $errorMsg]];
        }
    }

    /**
     * Idempotently generate a redemption token for a request.
     * Only executes if the request is APPROVED or REDEEMABLE and doesn't already have one.
     */
    public static function generateForRequest(RequestModel $request): ?OrderRedemption
    {
        if (! in_array($request->status, ['APPROVED', 'REDEEMABLE'])) {
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

        // TTL: admin override (FR-8.3) or default 3h (FR-8.2)
        $ttlMinutes = QrTtl::currentMinutes();

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
