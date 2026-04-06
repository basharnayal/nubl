<?php

namespace App\Http\Services;

use App\Models\Ewallet;
use App\Support\FinancialMath;
use App\Models\FundTransaction;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;

/**
 * Handles operations on the system's default wallet (Ewallet with owner_type = SYSTEM).
 * This wallet holds pooled funds from donor donations and is used to fund recipient requests.
 *
 * Integration points:
 * - addFundsFromDonation() after donor payment is successfully processed
 * - transferToProviderForRequest() when recipient redeems (QR scan at provider)
 */
class SystemWalletService
{
    public function __construct(
        private AuditService $auditService,
        private AllocationService $allocationService
    ) {}
    /**
     * Add funds to the system wallet when a donor's payment is confirmed.
     *
     * IMPORTANT — This method is designed to be called from within an existing
     * DB::transaction (e.g. PaymentService::processCallbackPaymentState) that
     * already holds a lockForUpdate on the Payment row. The lock on Payment
     * serialises concurrent callbacks for the same payment_id, so the
     * idempotency check here cannot race.
     *
     * If called outside a transaction, the idempotency check is still present
     * but is NOT race-safe on its own.
     *
     * @param  float  $amount  Amount to add (SAR)
     * @param  int  $donorId  User ID of the donor
     * @param  int|null  $paymentId  Optional payment record ID
     * @return void
     */
    public function addFundsFromDonation(float $amount, int $donorId, ?int $paymentId = null): void
    {
        // Idempotency: do not create duplicate fund_transaction for same payment.
        // Safe because the caller holds a FOR UPDATE lock on the Payment row,
        // serialising concurrent attempts for the same payment_id.
        if ($paymentId !== null && FundTransaction::where('payment_id', $paymentId)->exists()) {
            return;
        }

        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->firstOrFail();

        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donorId,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => $amount,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $paymentId,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $this->auditService->log('wallet', 'donation_added', [
            'amount' => $amount,
            'donor_id' => $donorId,
            'payment_id' => $paymentId,
        ], $donorId);
    }

    /**
     * Get the system's default wallet.
     * Used when checking balance or performing operations on the pooled fund.
     *
     * @return Ewallet
     */
    public function getSystemWallet(): Ewallet
    {
        return Ewallet::where('owner_type', 'SYSTEM')->firstOrFail();
    }

    /**
     * Check if the system wallet has sufficient balance for a given amount.
     *
     * @param  float  $amount  Amount to check (SAR)
     * @return bool
     */
    public function hasSufficientBalance(float $amount): bool
    {
        $systemWallet = $this->getSystemWallet();

        return FinancialMath::compare(
            FinancialMath::normalize((string) ($systemWallet->getRawOriginal('balance') ?? $systemWallet->balance)),
            FinancialMath::normalize($amount)
        ) >= 0;
    }

    /**
     * Transfer amount from city fund (system wallet) to provider wallet.
     * Called at redemption (QR scan), not at approval.
     *
     * @param  RequestModel  $request  The request (must have reserved_amount and provider_id)
     * @param  int|null  $orderRedemptionId  Optional OrderRedemption ID for audit trail
     * @return void
     *
     * @throws \RuntimeException If system wallet has insufficient balance or provider has no wallet
     */
    public function transferToProviderForRequest(RequestModel $request, ?int $orderRedemptionId = null): void
    {
        $amount = FinancialMath::normalize(
            (string) ($request->getRawOriginal('reserved_amount') ?? $request->reserved_amount)
        );

        if (FinancialMath::compare($amount, '0.00') <= 0) {
            return;
        }

        if (! $this->hasSufficientBalance((float) $amount)) {
            throw new \RuntimeException('City fund has insufficient balance for this request.');
        }

        $this->allocationService->allocateToRequest($request->id, (float) $amount);

        $systemWallet = $this->getSystemWallet();

        $providerProfile = ProviderProfile::where('user_id', $request->provider_id)->firstOrFail();
        $providerWallet = $providerProfile->ewallet ?? $providerProfile->ewallet()->create([
            'owner_type' => 'PROVIDER',
            'balance' => 0,
            'status' => true,
        ]);

        // Create FundTransactions (balance calculated from transactions: sum(IN) - sum(OUT))
        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => (float) $amount,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => null,
            'request_id' => $request->id,
            'order_redemption_id' => $orderRedemptionId,
        ]);

        FundTransaction::create([
            'wallet_id' => $providerWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => (float) $amount,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => $request->id,
            'order_redemption_id' => $orderRedemptionId,
        ]);

        $this->auditService->log('wallet', 'payout_to_provider', [
            'request_id' => $request->id,
            'amount' => (float) $amount,
            'provider_id' => $request->provider_id,
        ], auth()->id());
    }
}
