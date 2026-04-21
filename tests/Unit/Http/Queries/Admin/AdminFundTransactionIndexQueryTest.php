<?php

namespace Tests\Unit\Http\Queries\Admin;

use App\Http\Queries\Admin\AdminFundTransactionIndexQuery;
use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminFundTransactionIndexQueryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function numeric_search_matches_transaction_id_payment_id_or_request_id(): void
    {
        $donor = User::factory()->create();
        $recipient = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        $provider = User::factory()->create(['membership_type' => User::MEMBERSHIP_PROVIDER]);
        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'TX-SEARCH',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
        ]);
        $requestModel = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 50,
            'status' => 'REQUESTED',
        ]);

        $txByPayment = FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 100,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
        ]);
        $txByRequest = FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 50,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => $payment->id,
            'request_id' => $requestModel->id,
        ]);

        $query = new AdminFundTransactionIndexQuery;

        $byPayment = Request::create('/admin/finances/fund-transactions', 'GET', ['search' => (string) $payment->id]);
        $byRequest = Request::create('/admin/finances/fund-transactions', 'GET', ['search' => (string) $requestModel->id]);
        $byId = Request::create('/admin/finances/fund-transactions', 'GET', ['search' => (string) $txByPayment->id]);

        $this->assertContains($txByPayment->id, $query->buildQuery($byPayment)->pluck('id')->all());
        $this->assertContains($txByRequest->id, $query->buildQuery($byRequest)->pluck('id')->all());
        $this->assertContains($txByPayment->id, $query->buildQuery($byId)->pluck('id')->all());
    }

    #[Test]
    public function wallet_type_direction_source_donor_and_date_filters_are_combined(): void
    {
        $donor = User::factory()->create();
        $otherDonor = User::factory()->create();

        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $paymentA = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'WALLET-FILTER-A',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 55,
        ]);
        $paymentB = Payment::create([
            'sponsor_id' => $otherDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'WALLET-FILTER-B',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 65,
        ]);
        $paymentC = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'WALLET-FILTER-C',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 55,
        ]);
        $paymentD = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'WALLET-FILTER-D',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 55,
        ]);

        $included = FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 55,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $paymentA->id,
        ]);
        $included->created_at = now()->subDay();
        $included->updated_at = now()->subDay();
        $included->save();

        FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => $otherDonor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 65,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $paymentB->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 55,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => $paymentC->id,
        ]);
        $outOfDate = FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 55,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $paymentD->id,
        ]);
        $outOfDate->created_at = now()->subDays(20);
        $outOfDate->updated_at = now()->subDays(20);
        $outOfDate->save();

        $request = Request::create('/admin/finances/fund-transactions', 'GET', [
            'wallet_type' => 'SYSTEM',
            'direction' => FundTransaction::DIRECTION_IN,
            'source' => FundTransaction::SOURCE_DONATION,
            'donor_id' => $donor->id,
            'date_from' => now()->subDays(2)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $ids = (new AdminFundTransactionIndexQuery)->buildQuery($request)->pluck('id')->all();

        $this->assertSame([$included->id], $ids);
    }

    #[Test]
    public function provider_user_filter_limits_results_to_provider_owned_wallets(): void
    {
        [$providerA, $walletA] = $this->createProviderWithWallet();
        [, $walletB] = $this->createProviderWithWallet();

        $donor = User::factory()->create();
        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'PROVIDER-FILTER',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 88,
        ]);
        $otherPayment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'PROVIDER-FILTER-OTHER',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 88,
        ]);

        $expected = FundTransaction::create([
            'wallet_id' => $walletA->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 88,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => $payment->id,
        ]);

        FundTransaction::create([
            'wallet_id' => $walletB->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 88,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => $otherPayment->id,
        ]);

        $request = Request::create('/admin/finances/fund-transactions', 'GET', [
            'provider_user_id' => $providerA->id,
        ]);

        $ids = (new AdminFundTransactionIndexQuery)->buildQuery($request)->pluck('id')->all();

        $this->assertSame([$expected->id], $ids);
    }

    /**
     * @return array{User, Ewallet}
     */
    private function createProviderWithWallet(): array
    {
        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $profile = ProviderProfile::create([
            'user_id' => $user->id,
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider',
            'phone_number' => '966511111111',
            'email' => $user->email,
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Store',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
        ]);

        $wallet = Ewallet::query()->where('owner_id', $profile->id)->where('owner_type', 'PROVIDER')->firstOrFail();

        return [$user, $wallet];
    }
}
