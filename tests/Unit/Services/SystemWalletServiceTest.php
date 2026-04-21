<?php

namespace Tests\Unit\Services;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AuditService;
use App\Services\SystemWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function add_funds_from_donation_is_idempotent_per_payment_id(): void
    {
        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
        $donor = User::factory()->create();
        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50.00,
        ]);

        $allocationService = Mockery::mock(AllocationService::class);
        $auditService = Mockery::spy(AuditService::class);
        $this->app->instance(AllocationService::class, $allocationService);
        $this->app->instance(AuditService::class, $auditService);

        $service = app(SystemWalletService::class);
        $service->addFundsFromDonation(50.00, $donor->id, $payment->id);
        $service->addFundsFromDonation(50.00, $donor->id, $payment->id);

        $this->assertDatabaseCount('fund_transactions', 1);
        $this->assertDatabaseHas('fund_transactions', [
            'wallet_id' => $systemWallet->id,
            'payment_id' => $payment->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'direction' => FundTransaction::DIRECTION_IN,
        ]);
        $this->assertSame('50.00', (string) $systemWallet->fresh()->balance);

        $auditService->shouldHaveReceived('log')
            ->with(
                'wallet',
                'donation_added',
                Mockery::on(fn (array $data): bool => ($data['payment_id'] ?? null) === $payment->id),
                $donor->id
            )
            ->once();
    }

    #[Test]
    public function has_sufficient_balance_uses_system_wallet_balance(): void
    {
        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 100,
            'status' => true,
        ]);

        $this->app->instance(AllocationService::class, Mockery::mock(AllocationService::class));
        $this->app->instance(AuditService::class, Mockery::mock(AuditService::class));

        $service = app(SystemWalletService::class);

        $this->assertTrue($service->hasSufficientBalance(99.99));
        $this->assertTrue($service->hasSufficientBalance(100.00));
        $this->assertFalse($service->hasSufficientBalance(100.01));
    }

    #[Test]
    public function transfer_to_provider_for_request_creates_out_and_in_transactions(): void
    {
        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 200,
            'status' => true,
        ]);

        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $recipient = User::factory()->create();
        $this->createProviderProfile($provider);

        // Force path where provider wallet is lazily created in the service.
        $provider->providerProfile->ewallet()->delete();
        $this->assertNull($provider->providerProfile->fresh()->ewallet);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 75.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('allocateToRequest')
            ->once()
            ->with($request->id, 75.0);

        $auditService = Mockery::spy(AuditService::class);
        $this->app->instance(AllocationService::class, $allocationService);
        $this->app->instance(AuditService::class, $auditService);

        $service = app(SystemWalletService::class);
        $service->transferToProviderForRequest($request);

        $providerWallet = $provider->providerProfile->fresh()->ewallet;
        $this->assertNotNull($providerWallet);

        $this->assertDatabaseHas('fund_transactions', [
            'wallet_id' => $systemWallet->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'direction' => FundTransaction::DIRECTION_OUT,
            'request_id' => $request->id,
            'order_redemption_id' => null,
        ]);
        $this->assertDatabaseHas('fund_transactions', [
            'wallet_id' => $providerWallet->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'direction' => FundTransaction::DIRECTION_IN,
            'request_id' => $request->id,
            'order_redemption_id' => null,
        ]);

        $this->assertSame('125.00', (string) $systemWallet->fresh()->balance);
        $this->assertSame('75.00', (string) $providerWallet->fresh()->balance);
    }

    #[Test]
    public function transfer_to_provider_for_request_is_idempotent_per_request(): void
    {
        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 200,
            'status' => true,
        ]);

        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $recipient = User::factory()->create();
        $this->createProviderProfile($provider);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 75.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldReceive('allocateToRequest')
            ->once()
            ->with($request->id, 75.0);

        $auditService = Mockery::spy(AuditService::class);
        $this->app->instance(AllocationService::class, $allocationService);
        $this->app->instance(AuditService::class, $auditService);

        $service = app(SystemWalletService::class);
        $service->transferToProviderForRequest($request);
        $service->transferToProviderForRequest($request->fresh());

        $providerWallet = $provider->providerProfile->fresh()->ewallet;

        $this->assertSame(1, FundTransaction::where('request_id', $request->id)
            ->where('source', FundTransaction::SOURCE_PAYOUT)
            ->where('direction', FundTransaction::DIRECTION_OUT)
            ->count());
        $this->assertSame(1, FundTransaction::where('request_id', $request->id)
            ->where('source', FundTransaction::SOURCE_PAYOUT)
            ->where('direction', FundTransaction::DIRECTION_IN)
            ->count());
        $this->assertSame('125.00', (string) $systemWallet->fresh()->balance);
        $this->assertSame('75.00', (string) $providerWallet->fresh()->balance);
    }

    #[Test]
    public function transfer_to_provider_for_request_throws_when_city_fund_is_insufficient(): void
    {
        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 10,
            'status' => true,
        ]);

        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $recipient = User::factory()->create();
        $this->createProviderProfile($provider);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $allocationService = Mockery::mock(AllocationService::class);
        $allocationService->shouldNotReceive('allocateToRequest');
        $this->app->instance(AllocationService::class, $allocationService);
        $this->app->instance(AuditService::class, Mockery::mock(AuditService::class));

        $service = app(SystemWalletService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('insufficient balance');

        $service->transferToProviderForRequest($request);
    }

    private function createProviderProfile(User $provider): ProviderProfile
    {
        return ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider Ar',
            'full_name_en' => 'Provider En',
            'phone_number' => '966501112233',
            'email' => $provider->email,
            'business_name_ar' => 'Business Ar',
            'business_name_en' => 'Business En',
            'unified_number' => '7000123456',
            'business_category' => ['retail'],
            'address_ar' => 'Address Ar',
            'address_en' => 'Address En',
            'city' => 'Riyadh',
            'region' => 'central',
        ]);
    }
}
