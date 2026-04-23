<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\User;
use App\Support\FinancialMath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SystemWalletController (testing environments only)
 *
 * Allows automation to set the SYSTEM wallet (city fund) balance to an exact
 * amount and undo the most recent set, without needing an admin session.
 *
 * All endpoints are protected by TestingEnvironmentOnly middleware via routes/testing.php.
 *
 * Endpoints (prefixed with /_testing/system-wallet):
 *   GET  /                  → show current system wallet balance + undo availability
 *   POST /set-balance        → set system wallet balance to an exact amount (SAR)
 *   POST /undo-set-balance   → revert to the balance before the last set-balance
 *
 * Actor:
 * All ledger movements created here are attributed to donor-seed@nubl.com via sponsor_id.
 */
class SystemWalletController extends Controller
{
    private const UNDO_CACHE_KEY = 'testing:system_wallet:set_balance:undo_target';

    private function systemWalletOrFail(): Ewallet
    {
        return Ewallet::query()->where('owner_type', 'SYSTEM')->firstOrFail();
    }

    private function donorSeedOrFail(): User
    {
        return User::query()->where('email', 'donor-seed@nubl.com')->firstOrFail();
    }

    private function walletPayload(Ewallet $wallet): array
    {
        $balance = FinancialMath::normalize((string) ($wallet->getRawOriginal('balance') ?? $wallet->balance));
        $undoTarget = Cache::get(self::UNDO_CACHE_KEY);

        return [
            'wallet_id' => $wallet->id,
            'owner_type' => $wallet->owner_type,
            'balance' => (float) $balance,
            'undo' => [
                'available' => $undoTarget !== null,
                'target_balance' => $undoTarget !== null ? (float) FinancialMath::normalize((string) $undoTarget) : null,
            ],
        ];
    }

    /**
     * Set system wallet balance by creating IN/OUT FundTransaction(s).
     *
     * @return array{previous_balance: float, target_balance: float, current_balance: float}
     */
    private function setSystemWalletBalance(string $target, bool $captureUndo): array
    {
        $target = FinancialMath::normalize($target);
        $donor = $this->donorSeedOrFail();

        return DB::transaction(function () use ($target, $donor, $captureUndo): array {
            $wallet = Ewallet::query()
                ->where('owner_type', 'SYSTEM')
                ->lockForUpdate()
                ->firstOrFail();

            $current = FinancialMath::normalize((string) ($wallet->getRawOriginal('balance') ?? $wallet->balance));

            if ($captureUndo && Cache::get(self::UNDO_CACHE_KEY) === null) {
                // Store undo target only once until it is undone.
                Cache::forever(self::UNDO_CACHE_KEY, $current);
            }

            $diff = FinancialMath::sub($target, $current); // target - current

            if (FinancialMath::compare($diff, '0.00') > 0) {
                FundTransaction::create([
                    'wallet_id' => $wallet->id,
                    'sponsor_id' => $donor->id,
                    'source' => FundTransaction::SOURCE_DONATION,
                    'amount' => $diff,
                    'direction' => FundTransaction::DIRECTION_IN,
                    'payment_id' => null,
                    'request_id' => null,
                    'order_redemption_id' => null,
                ]);
            } elseif (FinancialMath::compare($diff, '0.00') < 0) {
                // Remove funds by creating an OUT entry. This is testing-only behavior.
                $outAmount = FinancialMath::sub('0.00', $diff); // abs(diff)
                FundTransaction::create([
                    'wallet_id' => $wallet->id,
                    'sponsor_id' => $donor->id,
                    'source' => FundTransaction::SOURCE_REFUND,
                    'amount' => $outAmount,
                    'direction' => FundTransaction::DIRECTION_OUT,
                    'payment_id' => null,
                    'request_id' => null,
                    'order_redemption_id' => null,
                ]);
            }

            $wallet->refresh();

            return [
                'previous_balance' => (float) $current,
                'target_balance' => (float) $target,
                'current_balance' => (float) FinancialMath::normalize((string) ($wallet->getRawOriginal('balance') ?? $wallet->balance)),
            ];
        });
    }

    /**
     * GET /_testing/system-wallet
     */
    public function show(): JsonResponse
    {
        $wallet = $this->systemWalletOrFail();

        return response()->json([
            'message' => 'System wallet retrieved.',
            'data' => $this->walletPayload($wallet),
        ]);
    }

    /**
     * POST /_testing/system-wallet/set-balance
     *
     * Body (JSON):
     *   { "amount": 1234.56 }
     */
    public function setBalance(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->setSystemWalletBalance((string) $request->input('amount'), true);

        return response()->json([
            'message' => 'System wallet balance set.',
            'data' => $result,
        ]);
    }

    /**
     * POST /_testing/system-wallet/undo-set-balance
     *
     * Reverts to the balance captured when set-balance was first called.
     */
    public function undoSetBalance(): JsonResponse
    {
        $undoTarget = Cache::get(self::UNDO_CACHE_KEY);
        if ($undoTarget === null) {
            return response()->json([
                'message' => 'No undo target found. Call set-balance first.',
            ], 422);
        }

        $result = $this->setSystemWalletBalance((string) $undoTarget, false);
        Cache::forget(self::UNDO_CACHE_KEY);

        return response()->json([
            'message' => 'System wallet balance restored (undo).',
            'data' => $result,
        ]);
    }
}

