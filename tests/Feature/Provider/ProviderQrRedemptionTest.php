<?php

namespace Tests\Feature\Provider;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\OrderRedemption;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\RedemptionService;
use App\Services\AuditService;
use App\Services\SystemWalletService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderQrRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->provider->assignRole('provider');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: build the minimal DB state required for a redeemable request.
    // ─────────────────────────────────────────────────────────────────────────

    private function createRedeemableRequest(): RequestModel
    {
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        // Provider profile (creates provider ewallet via model boot)
        ProviderProfile::firstOrCreate(
            ['user_id' => $this->provider->id],
            [
                'full_name_ar'      => 'مزود',
                'full_name_en'      => 'Provider',
                'phone_number'      => '966501234567',
                'email'             => $this->provider->email,
                'business_name_ar'  => 'متجر',
                'business_name_en'  => 'Shop',
                'unified_number'    => '7000123456',
                'business_category' => ['restaurant'],
                'address_ar'        => 'عنوان',
                'address_en'        => 'Address',
                'city'              => 'Riyadh',
                'region'            => 'central',
            ]
        );

        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $recipient->assignRole('recipient');

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name'        => 'Meal',
            'price'       => 30.00,
            'category'    => 'food',
            'is_active'   => true,
        ]);

        // System wallet + donor payment so transfer at redemption can succeed
        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id'   => null,
            'balance'    => 0,
            'status'     => true,
        ]);

        $donor   = User::factory()->create();
        $payment = Payment::create([
            'sponsor_id'         => $donor->id,
            'gateway'            => Payment::GATEWAY_MYFATOORAH,
            'status'             => Payment::STATUS_SUCCEEDED,
            'amount'             => 100,
        ]);
        FundTransaction::create([
            'wallet_id'          => $systemWallet->id,
            'sponsor_id'         => $donor->id,
            'source'             => FundTransaction::SOURCE_DONATION,
            'amount'             => 100,
            'direction'          => FundTransaction::DIRECTION_IN,
            'payment_id'         => $payment->id,
            'request_id'         => null,
            'order_redemption_id' => null,
        ]);
        $systemWallet->syncBalance();

        $request = RequestModel::create([
            'recipient_id'    => $recipient->id,
            'provider_id'     => $this->provider->id,
            'reserved_amount' => 30.00,
            'status'          => 'REDEEMABLE',
            'funding_source'  => 'CITY_FUND',
        ]);
        $request->items()->create([
            'menu_item_id'   => $menuItem->id,
            'quantity'       => 1,
            'price_snapshot' => 30.00,
        ]);

        return $request;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FR-9.1: Valid token → 200, status changes, audit logged
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function generated_redemption_tokens_store_raw_and_short_token_contracts(): void
    {
        $request = $this->createRedeemableRequest();

        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption);

        $rawToken = Crypt::decryptString($redemption->token_ciphertext);
        $shortToken = Crypt::decryptString($redemption->short_code_ciphertext);

        $this->assertSame(32, strlen($rawToken));
        $this->assertSame(9, strlen($shortToken));
        $this->assertSame(strtoupper($shortToken), $shortToken);
        $this->assertSame(hash('sha256', $rawToken), $redemption->token_code);
        $this->assertSame(hash('sha256', $shortToken), $redemption->short_code_hash);
    }

    #[Test]
    public function generated_redemption_ttl_is_clamped_to_configured_bounds(): void
    {
        config([
            'qr.ttl_minutes' => 180,
            'qr.ttl_minutes_min' => 15,
            'qr.ttl_minutes_max' => 720,
        ]);

        SystemSetting::setValue('qr.ttl_minutes', '1');
        $low = RedemptionService::generateForRequest($this->createRedeemableRequest());
        $this->assertNotNull($low);
        $this->assertSame(15, $low->ttl_minutes);

        SystemSetting::setValue('qr.ttl_minutes', '9999');
        $high = RedemptionService::generateForRequest($this->createRedeemableRequest());
        $this->assertNotNull($high);
        $this->assertSame(720, $high->ttl_minutes);
    }

    #[Test]
    public function redemption_generation_skips_unsupported_request_statuses(): void
    {
        $request = $this->createRedeemableRequest();
        $request->update(['status' => 'REQUESTED']);

        $this->assertNull(RedemptionService::generateForRequest($request->fresh()));
    }

    #[Test]
    public function redemption_generation_reuses_existing_token_for_request(): void
    {
        $request = $this->createRedeemableRequest();

        $first = RedemptionService::generateForRequest($request);
        $second = RedemptionService::generateForRequest($request->fresh('redemption'));

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertTrue($first->is($second));
    }

    #[Test]
    public function recipient_request_detail_backfills_legacy_redeemable_request_without_token(): void
    {
        $request = $this->createRedeemableRequest();
        $this->assertFalse(OrderRedemption::where('request_id', $request->id)->exists());

        $this->actingAs($request->recipient)
            ->get(route('recipient.requests.show', $request->id))
            ->assertOk();

        $redemption = OrderRedemption::where('request_id', $request->id)->first();
        $this->assertNotNull($redemption);
        $this->assertSame('PENDING', $redemption->status);
        $this->assertSame($request->provider_id, $redemption->provider_id);
        $this->assertSame(32, strlen(Crypt::decryptString($redemption->token_ciphertext)));
        $this->assertSame(9, strlen(Crypt::decryptString($redemption->short_code_ciphertext)));
    }

    #[Test]
    public function valid_token_is_redeemed_and_returns_200_fr_9_1(): void
    {
        $request    = $this->createRedeemableRequest();
        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption, 'Redemption token must be generated for REDEEMABLE request.');

        // ProviderQrController hashes the submitted token with SHA-256 before lookup; DB stores hash(raw).
        $rawQrToken = Crypt::decryptString($redemption->token_ciphertext);
        $response = $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), [
                'token' => $rawQrToken,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => __('Successfully redeemed!')]);

        // DB: redemption is now REDEEMED
        $this->assertDatabaseHas('order_redemptions', [
            'id'     => $redemption->id,
            'status' => 'REDEEMED',
        ]);

        // Audit log must exist
        $this->assertDatabaseHas('activity_log', [
            'description' => 'redemption.redeemed',
        ]);

        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->firstOrFail();
        $providerWallet = ProviderProfile::where('user_id', $this->provider->id)->firstOrFail()->ewallet;

        $this->assertSame('70.00', (string) $systemWallet->fresh()->balance);
        $this->assertSame('30.00', (string) $providerWallet->fresh()->balance);

        $this->assertDatabaseHas('fund_transactions', [
            'wallet_id' => $systemWallet->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'direction' => FundTransaction::DIRECTION_OUT,
            'amount' => 30.00,
            'request_id' => $request->id,
            'order_redemption_id' => $redemption->id,
        ]);

        $this->assertDatabaseHas('fund_transactions', [
            'wallet_id' => $providerWallet->id,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'direction' => FundTransaction::DIRECTION_IN,
            'amount' => 30.00,
            'request_id' => $request->id,
            'order_redemption_id' => $redemption->id,
        ]);

        // Allocation links this request to the succeeded payment pool (FIFO pool is used in AllocationService;
        // this scenario uses a single payment, so one link is expected — multi-payment ordering is covered in AllocationFifoTest.)
        $this->assertDatabaseHas('request_payment_links', [
            'request_id' => $request->id,
            'amount' => 30.00,
        ]);

        $this->assertSame(1, RequestPaymentLink::where('request_id', $request->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FR-9.1: Second redemption of the same token → 409 Conflict
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function token_for_another_provider_returns_403_without_ledger_side_effects(): void
    {
        $request = $this->createRedeemableRequest();
        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption);

        $otherProvider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $otherProvider->assignRole('provider');

        $rawQrToken = Crypt::decryptString($redemption->token_ciphertext);

        $response = $this->actingAs($otherProvider)
            ->postJson(route('provider.qr.redeem'), [
                'token' => $rawQrToken,
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => __('This code is not valid for your account.')]);

        $this->assertDatabaseHas('order_redemptions', [
            'id' => $redemption->id,
            'status' => 'PENDING',
        ]);
        $this->assertSame(0, RequestPaymentLink::where('request_id', $request->id)->count());
        $this->assertSame(
            0,
            FundTransaction::where('request_id', $request->id)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->count()
        );
    }

    #[Test]
    public function provider_adopted_redemption_does_not_touch_city_fund_or_provider_wallet(): void
    {
        $request = $this->createRedeemableRequest();
        $request->update([
            'status' => 'APPROVED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);

        $redemption = RedemptionService::generateForRequest($request->fresh());
        $this->assertNotNull($redemption);

        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->firstOrFail();
        $providerWallet = ProviderProfile::where('user_id', $this->provider->id)->firstOrFail()->ewallet;
        $systemBalanceBefore = (string) $systemWallet->fresh()->balance;
        $providerBalanceBefore = (string) $providerWallet->fresh()->balance;

        $rawQrToken = Crypt::decryptString($redemption->token_ciphertext);

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $rawQrToken])
            ->assertStatus(200);

        $this->assertDatabaseHas('order_redemptions', [
            'id' => $redemption->id,
            'status' => 'REDEEMED',
        ]);
        $this->assertSame($systemBalanceBefore, (string) $systemWallet->fresh()->balance);
        $this->assertSame($providerBalanceBefore, (string) $providerWallet->fresh()->balance);
        $this->assertSame(0, RequestPaymentLink::where('request_id', $request->id)->count());
        $this->assertSame(
            0,
            FundTransaction::where('request_id', $request->id)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->count()
        );
    }

    #[Test]
    public function expired_token_returns_422_without_redeeming_or_transferring_funds(): void
    {
        $request = $this->createRedeemableRequest();
        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption);
        $redemption->update(['redeem_expires_at' => now()->subMinute()]);

        $rawQrToken = Crypt::decryptString($redemption->token_ciphertext);

        $response = $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $rawQrToken]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => __('This QR code has expired.')]);

        $this->assertDatabaseHas('order_redemptions', [
            'id' => $redemption->id,
            'status' => 'PENDING',
        ]);
        $this->assertSame(0, RequestPaymentLink::where('request_id', $request->id)->count());
        $this->assertSame(
            0,
            FundTransaction::where('request_id', $request->id)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->count()
        );
    }

    #[Test]
    public function non_pending_token_status_returns_422_without_side_effects(): void
    {
        $request = $this->createRedeemableRequest();
        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption);
        DB::statement('PRAGMA ignore_check_constraints = ON');
        $redemption->forceFill(['status' => 'CANCELLED'])->save();

        $rawQrToken = Crypt::decryptString($redemption->token_ciphertext);

        $response = $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $rawQrToken]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => __('This code cannot be redeemed.')]);
        $this->assertSame(0, RequestPaymentLink::where('request_id', $request->id)->count());
        $this->assertSame(
            0,
            FundTransaction::where('request_id', $request->id)
                ->where('source', FundTransaction::SOURCE_PAYOUT)
                ->count()
        );
    }

    #[Test]
    public function redemption_rolls_back_and_returns_500_when_transfer_service_fails(): void
    {
        $request = $this->createRedeemableRequest();
        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption);

        $walletService = Mockery::mock(SystemWalletService::class);
        $walletService->shouldReceive('transferToProviderForRequest')
            ->once()
            ->withArgs(fn (RequestModel $requestModel, int $redemptionId): bool => $requestModel->is($request) && $redemptionId === $redemption->id)
            ->andThrow(new \RuntimeException('forced transfer failure'));

        $service = new RedemptionService(app(AuditService::class), $walletService);

        $result = $service->redeem($redemption->token_code, $this->provider->id);

        $this->assertSame(500, $result['status']);
        $this->assertSame('forced transfer failure', $result['body']['error']);
        $this->assertSame('PENDING', $redemption->fresh()->status);
    }

    #[Test]
    public function already_redeemed_token_returns_409_fr_9_1(): void
    {
        $request    = $this->createRedeemableRequest();
        $redemption = RedemptionService::generateForRequest($request);
        $this->assertNotNull($redemption);

        $rawQrToken = Crypt::decryptString($redemption->token_ciphertext);

        // First redemption
        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $rawQrToken])
            ->assertStatus(200);

        // Second attempt on the same token
        $response = $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $rawQrToken]);

        $response->assertStatus(409);
        $response->assertJsonFragment(['error' => __('This code has already been used.')]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FR-9.2 / FR-9.3 (existing tests)
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function invalid_token_returns_404_within_one_second_fr_9_2(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), [
                'token' => 'invalid-token-that-will-not-match',
            ]);

        $elapsed = microtime(true) - $start;

        $response->assertStatus(404);
        $response->assertJsonFragment(['error' => __('Invalid token.')]);
        $this->assertLessThan(1.0, $elapsed, 'Invalid token response should complete within 1 second (FR-9.2 smoke test).');
    }

    #[Test]
    public function rate_limit_allows_two_attempts_then_429_fr_9_3(): void
    {
        $token = 'x';

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $token])
            ->assertStatus(404);

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $token])
            ->assertStatus(404);

        $this->actingAs($this->provider)
            ->postJson(route('provider.qr.redeem'), ['token' => $token])
            ->assertStatus(429)
            ->assertJsonFragment(['error' => __('Too many attempts, wait 30 seconds.')]);
    }
}
