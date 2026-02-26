<?php

namespace App\Http\Services;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;

/**
 * Handles operations on the system's default wallet (Ewallet with owner_type = SYSTEM).
 * This wallet holds pooled funds from donor donations and is used to fund recipient requests.
 *
 * Integration points:
 * - addFundsFromDonation() after donor payment is successfully processed
 * - transferToProviderForRequest() when provider clicks "Approve (City Fund)"
 */
class SystemWalletService
{
    /**
     * Add funds to the system wallet when a donor's payment is confirmed.
     * Call this from DonationService::confirmDonation() (or equivalent) after payment verification.
     *
     * @param  float  $amount  Amount to add (SAR)
     * @param  int  $donorId  User ID of the donor
     * @param  int|null  $paymentId  Optional payment record ID (when payments table exists)
     * @return void
     */
    public function addFundsFromDonation(float $amount, int $donorId, ?int $paymentId = null): void
    {
        // 1. Get the default system wallet
        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->firstOrFail();

        // 2. Increment the wallet balance by $amount
        $systemWallet->increment('balance', $amount);

        // 3. Create a FundTransaction record for audit trail
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

        return (float) $systemWallet->balance >= $amount;
    }

    /**
     * Transfer amount from city fund (system wallet) to provider wallet when provider
     * clicks "Approve (City Fund)". Deducts from system wallet and credits provider wallet.
     *
     * @param  RequestModel  $request  The request being approved (must have reserved_amount and provider_id)
     * @return void
     *
     * @throws \RuntimeException If system wallet has insufficient balance or provider has no wallet
     */
    public function transferToProviderForRequest(RequestModel $request): void
    {
        $amount = (float) $request->reserved_amount;

        if ($amount <= 0) {
            return;
        }

        if (! $this->hasSufficientBalance($amount)) {
            throw new \RuntimeException('City fund has insufficient balance for this request.');
        }

        $systemWallet = $this->getSystemWallet();

        $providerProfile = ProviderProfile::where('user_id', $request->provider_id)->firstOrFail();
        $providerWallet = $providerProfile->ewallet ?? $providerProfile->ewallet()->create([
            'owner_type' => 'PROVIDER',
            'balance' => 0,
            'status' => true,
        ]);

        // 1. Deduct from system wallet
        $systemWallet->decrement('balance', $amount);

        // 2. Credit provider wallet
        $providerWallet->increment('balance', $amount);

        // 3. Create audit records
        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => $amount,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => null,
            'request_id' => $request->id,
            'order_redemption_id' => null,
        ]);

        FundTransaction::create([
            'wallet_id' => $providerWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => $amount,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => $request->id,
            'order_redemption_id' => null,
        ]);
    }
}
