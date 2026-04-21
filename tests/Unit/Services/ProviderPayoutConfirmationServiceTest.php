<?php

namespace Tests\Unit\Services;

use App\Models\FundTransaction;
use App\Models\ProviderPayout;
use App\Models\ProviderPayoutItem;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Notifications\ProviderPayoutTransferredNotification;
use App\Services\AuditService;
use App\Services\ProviderPayoutConfirmationService;
use App\Services\ProviderPayoutLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderPayoutConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->provider->assignRole('provider');

        $this->createProviderProfile($this->provider);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function confirm_transfers_payout_and_notifies_provider(): void
    {
        Notification::fake();

        $payout = $this->createPendingPayoutWithSingleItem('60.00');

        $audit = Mockery::spy(AuditService::class);
        $service = new ProviderPayoutConfirmationService(
            app(ProviderPayoutLedgerService::class),
            $audit
        );

        $service->confirm($payout, $this->admin, 'REF-001', null, 'confirmed');

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_TRANSFERRED, $payout->status);
        $this->assertSame($this->admin->id, $payout->confirmed_by);
        $this->assertNotNull($payout->confirmed_at);
        $this->assertNotNull($payout->fund_transaction_out_id);

        $out = FundTransaction::findOrFail($payout->fund_transaction_out_id);
        $this->assertSame(FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT, $out->source);
        $this->assertSame(FundTransaction::DIRECTION_OUT, $out->direction);
        $this->assertSame($payout->id, $out->provider_payout_id);
        $this->assertSame('60.00', (string) $out->amount);

        $this->assertSame('0.00', (string) $payout->providerWallet->fresh()->balance);

        Notification::assertSentTo($this->provider, ProviderPayoutTransferredNotification::class);

        $audit->shouldHaveReceived('log')
            ->with(
                'provider_payout',
                'payout_request_confirmed',
                Mockery::on(fn (array $data): bool => ($data['provider_payout_id'] ?? null) === $payout->id),
                $this->admin->id
            )
            ->once();
    }

    #[Test]
    public function reject_marks_pending_payout_as_rejected(): void
    {
        $payout = $this->createPendingPayoutWithSingleItem('60.00');

        $audit = Mockery::spy(AuditService::class);
        $service = new ProviderPayoutConfirmationService(
            app(ProviderPayoutLedgerService::class),
            $audit
        );

        $service->reject($payout, $this->admin, 'rejected');

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_REJECTED, $payout->status);
        $this->assertSame($this->admin->id, $payout->rejected_by);
        $this->assertNotNull($payout->rejected_at);
    }

    #[Test]
    public function cancel_marks_pending_payout_as_cancelled(): void
    {
        $payout = $this->createPendingPayoutWithSingleItem('60.00');

        $audit = Mockery::spy(AuditService::class);
        $service = new ProviderPayoutConfirmationService(
            app(ProviderPayoutLedgerService::class),
            $audit
        );

        $service->cancel($payout, $this->admin, 'cancelled');

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_CANCELLED, $payout->status);
        $this->assertSame($this->admin->id, $payout->cancelled_by);
        $this->assertNotNull($payout->cancelled_at);
    }

    #[Test]
    public function confirm_throws_for_non_confirmable_status(): void
    {
        $payout = $this->createPendingPayoutWithSingleItem('60.00');
        $payout->update(['status' => ProviderPayout::STATUS_TRANSFERRED]);

        $audit = Mockery::spy(AuditService::class);
        $service = new ProviderPayoutConfirmationService(
            app(ProviderPayoutLedgerService::class),
            $audit
        );

        $this->expectException(\RuntimeException::class);

        $service->confirm($payout->fresh(), $this->admin, 'REF-FAIL', null, null);
    }

    private function createProviderProfile(User $provider): ProviderProfile
    {
        return ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider Ar',
            'full_name_en' => 'Provider En',
            'phone_number' => '966501234567',
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

    private function createPendingPayoutWithSingleItem(string $amount): ProviderPayout
    {
        $wallet = $this->provider->providerProfile->ewallet;

        $earning = FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => $amount,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => null,
        ]);

        $payout = ProviderPayout::create([
            'provider_id' => $this->provider->id,
            'provider_wallet_id' => $wallet->id,
            'week_start_at' => now()->subWeek(),
            'week_end_at' => now()->subDay(),
            'scheduled_at' => now(),
            'amount' => $amount,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
        ]);

        ProviderPayoutItem::create([
            'provider_payout_id' => $payout->id,
            'fund_transaction_id' => $earning->id,
            'amount' => $amount,
        ]);

        return $payout;
    }
}
