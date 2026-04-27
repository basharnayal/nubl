<?php

namespace Tests\Unit\Models;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderPayout;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function relationships_for_payouts_redemptions_and_profiles_are_resolved(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $donor = User::factory()->create();

        $providerProfile = ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => 'Provider',
            'phone_number' => '966500123456',
            'email' => $provider->email,
            'business_name_ar' => 'Shop',
            'business_name_en' => 'Shop',
            'unified_number' => '7000000999',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'location' => null,
        ]);
        $providerWallet = $providerProfile->ewallet
            ?? Ewallet::create([
                'owner_type' => 'PROVIDER',
                'owner_id' => $providerProfile->id,
                'balance' => 0,
                'status' => true,
            ]);

        $operating = ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => ['sun' => ['closed' => true]],
            'daily_capacity' => 10,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'pickup_notes' => null,
        ]);

        $kyc = RecipientKycDetails::create([
            'user_id' => $recipient->id,
            'income_band' => '1000-1500',
            'household_size' => 3,
            'marital_status' => 'single',
            'is_student' => false,
        ]);

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 30.00,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 30.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Meal',
            'description' => 'desc',
            'price' => 30.00,
            'category' => 'Meals',
            'is_active' => true,
        ]);
        $request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 30.00,
        ]);

        $link = RequestPaymentLink::create([
            'payment_id' => $payment->id,
            'request_id' => $request->id,
            'amount' => 30.00,
        ]);

        $redemption = OrderRedemption::create([
            'request_id' => $request->id,
            'provider_id' => $provider->id,
            'token_code' => hash('sha256', 'token-'.$request->id),
            'short_code_hash' => hash('sha256', 'short-'.$request->id),
            'token_ciphertext' => 'cipher',
            'short_code_ciphertext' => 'cipher-short',
            'ttl_minutes' => 180,
            'redeem_expires_at' => now()->addHour(),
            'status' => 'PENDING',
        ]);

        $payout = ProviderPayout::create([
            'provider_id' => $provider->id,
            'provider_wallet_id' => $providerWallet->id,
            'week_start_at' => now()->subWeek(),
            'week_end_at' => now()->subDay(),
            'scheduled_at' => now(),
            'amount' => 30.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            'confirmed_by' => $admin->id,
            'rejected_by' => $admin->id,
            'cancelled_by' => $admin->id,
        ]);

        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
        $outTx = FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
            'amount' => 30.00,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => $payout->id,
        ]);
        $payout->update(['fund_transaction_out_id' => $outTx->id]);

        $this->assertTrue($redemption->provider->is($provider));
        $this->assertTrue($providerProfile->menuItems->first()->is($menuItem));
        $this->assertTrue($operating->user->is($provider));
        $this->assertTrue($kyc->user->is($recipient));
        $this->assertTrue($request->requestPaymentLinks->first()->is($link));
        $this->assertTrue($outTx->providerPayout->is($payout));
        $this->assertTrue($payout->confirmedBy->is($admin));
        $this->assertTrue($payout->rejectedBy->is($admin));
        $this->assertTrue($payout->cancelledBy->is($admin));
        $this->assertTrue($payout->fundTransactionOut->is($outTx));
        $this->assertTrue($provider->providerPayouts->first()->is($payout));
    }

    #[Test]
    public function user_scopes_and_email_verification_helpers_work_as_expected(): void
    {
        $active = User::factory()->unverified()->create(['is_active' => true]);
        $inactive = User::factory()->create(['is_active' => false]);

        $this->assertSame([$active->id], User::query()->active()->pluck('id')->all());
        $this->assertSame([$inactive->id], User::query()->inactive()->pluck('id')->all());

        config(['app.email_verification_enabled' => false]);
        $this->assertFalse(User::emailVerificationRequired());
        $this->assertTrue($active->fresh()->isEmailVerified());

        config(['app.email_verification_enabled' => true]);
        $this->assertTrue(User::emailVerificationRequired());
        $this->assertFalse($active->fresh()->isEmailVerified());

        $active->forceFill(['email_verified_at' => now()])->save();
        $this->assertTrue($active->fresh()->isEmailVerified());
    }
}
