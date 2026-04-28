<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderPayout;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Notifications\ProviderPayoutPendingAdminNotification;
use App\Notifications\ProviderPayoutTransferredNotification;
use App\Services\AuditService;
use App\Services\ProviderPayoutGenerationService;
use App\Services\ProviderPayoutLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderPayoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private User $admin;

    private Ewallet $providerWallet;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach (['admin', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $this->provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966501234567',
            'email' => $this->provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Shop',
            'unified_number' => '7000123456',
            'business_category' => ['retail'],
            'address_ar' => 'الرياض',
            'address_en' => 'Riyadh',
            'city' => 'Riyadh',
            'region' => 'central',
        ]);

        $this->providerWallet = $this->provider->providerProfile->ewallet;
        $this->assertNotNull($this->providerWallet);

        FundTransaction::create([
            'wallet_id' => $this->providerWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 60.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => null,
        ]);

        $this->providerWallet->refresh();
    }

    #[Test]
    public function weekly_payout_request_created_for_eligible_unsettled_earning_credits(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));

        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());

        $this->assertCount(1, $ids);
        $payout = ProviderPayout::findOrFail($ids[0]);
        $this->assertSame(ProviderPayout::STATUS_PENDING_ADMIN_REVIEW, $payout->status);
        $this->assertSame('60.00', (string) $payout->amount);
        $this->assertSame(1, $payout->items()->count());

        Carbon::setTestNow();
    }

    #[Test]
    public function generation_notifies_admins_with_payload_and_audits_creation(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));

        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());

        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);

        Notification::assertSentTo(
            $this->admin,
            ProviderPayoutPendingAdminNotification::class,
            function (ProviderPayoutPendingAdminNotification $notification) use ($payout) {
                $payload = $notification->toArray($this->admin);

                $this->assertSame('provider_payout_pending_review', $payload['type']);
                $this->assertSame($payout->id, $payload['provider_payout_id']);
                $this->assertStringContainsString('60.00', $payload['message']);
                $this->assertSame(route('admin.finances.provider-payouts.show', $payout), $payload['url']);

                return true;
            }
        );

        $activity = Activity::query()
            ->where('description', 'provider_payout.payout_request_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($payout->id, $activity->properties->get('provider_payout_id'));
        $this->assertSame($this->provider->id, $activity->properties->get('provider_id'));
        $this->assertSame('60.00', $activity->properties->get('amount'));
    }

    #[Test]
    public function weekly_generation_returns_empty_while_cache_lock_is_held(): void
    {
        $lock = Cache::lock('provider-payout-generate-weekly', 600);
        $this->assertTrue($lock->get());

        try {
            Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
            $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
            Carbon::setTestNow();

            $this->assertSame([], $ids);
            $this->assertDatabaseCount('provider_payouts', 0);
        } finally {
            $lock->release();
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function weekly_generation_releases_cache_lock_when_generation_throws(): void
    {
        $throwingLedger = new class extends ProviderPayoutLedgerService
        {
            public function eligibleEarningTransactions(Ewallet $providerWallet): Collection
            {
                throw new \RuntimeException('ledger unavailable');
            }
        };

        $service = new ProviderPayoutGenerationService($throwingLedger, app(AuditService::class));

        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));

        try {
            $service->generateWeeklyAt(Carbon::now());
            $this->fail('Expected generation to surface the ledger failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('ledger unavailable', $e->getMessage());
        }

        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $this->assertCount(1, $ids);
    }

    #[Test]
    public function weekly_generation_command_creates_payouts_for_supplied_run_time(): void
    {
        $this->artisan('provider-payouts:generate-weekly', [
            '--at' => '2026-04-06T00:00:00+03:00',
        ])
            ->expectsOutput('Created 1 payout request(s).')
            ->assertSuccessful();

        $this->assertDatabaseCount('provider_payouts', 1);
        $this->assertSame(
            ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            ProviderPayout::query()->firstOrFail()->status
        );
    }

    #[Test]
    public function no_payout_created_when_total_below_minimum(): void
    {
        FundTransaction::where('wallet_id', $this->providerWallet->id)->delete();
        $this->providerWallet->update(['balance' => 0]);

        FundTransaction::create([
            'wallet_id' => $this->providerWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 40.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => null,
        ]);
        $this->providerWallet->refresh();

        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $this->assertSame([], $ids);
    }

    #[Test]
    public function no_duplicate_payout_for_same_provider_and_same_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $svc = app(ProviderPayoutGenerationService::class);
        $first = $svc->generateWeeklyAt(Carbon::now());
        $second = $svc->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $this->assertCount(1, $first);
        $this->assertSame([], $second);
    }

    #[Test]
    public function confirming_payout_creates_provider_wallet_out_transaction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);
        $balanceBefore = (float) $this->providerWallet->fresh()->balance;

        $receipt = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout), [
                'reference_number' => 'REF-123',
                'receipt' => $receipt,
                'admin_note' => 'ok',
            ])
            ->assertRedirect(route('admin.finances.provider-payouts.show', $payout));

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_TRANSFERRED, $payout->status);
        $this->assertNotNull($payout->fund_transaction_out_id);

        $out = FundTransaction::findOrFail($payout->fund_transaction_out_id);
        $this->assertSame(FundTransaction::DIRECTION_OUT, $out->direction);
        $this->assertSame(FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT, $out->source);
        $this->assertSame($payout->id, $out->provider_payout_id);

        $this->assertEqualsWithDelta($balanceBefore - 60, (float) $this->providerWallet->fresh()->balance, 0.001);
    }

    #[Test]
    public function confirming_payout_notifies_provider_with_payload_and_audits_confirmation(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout), [
                'reference_number' => 'REF-NOTIFY',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
                'admin_note' => 'sent',
            ])
            ->assertRedirect(route('admin.finances.provider-payouts.show', $payout));

        $payout->refresh();

        Notification::assertSentTo(
            $this->provider,
            ProviderPayoutTransferredNotification::class,
            function (ProviderPayoutTransferredNotification $notification) use ($payout) {
                $payload = $notification->toArray($this->provider);

                $this->assertSame('provider_payout_transferred', $payload['type']);
                $this->assertSame($payout->id, $payload['provider_payout_id']);
                $this->assertStringContainsString('60.00', $payload['message']);
                $this->assertSame(route('provider.dashboard'), $payload['url']);

                return true;
            }
        );

        $activity = Activity::query()
            ->where('description', 'provider_payout.payout_request_confirmed')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame($payout->id, $activity->properties->get('provider_payout_id'));
        $this->assertSame($payout->fund_transaction_out_id, $activity->properties->get('fund_transaction_id'));
        $this->assertSame('60.00', $activity->properties->get('amount'));
        $this->assertSame($this->provider->id, $activity->properties->get('provider_id'));
    }

    #[Test]
    public function cannot_confirm_twice(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);
        $receipt = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout), [
                'reference_number' => 'REF-1',
                'receipt' => $receipt,
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout->fresh()), [
                'reference_number' => 'REF-2',
                'receipt' => UploadedFile::fake()->create('receipt2.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHas('error');
    }

    #[Test]
    public function cannot_confirm_when_payout_items_no_longer_match_payout_amount(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);
        $payout->items()->firstOrFail()->update(['amount' => 59.00]);

        $outCountBefore = FundTransaction::where('wallet_id', $this->providerWallet->id)
            ->where('direction', FundTransaction::DIRECTION_OUT)
            ->count();

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout), [
                'reference_number' => 'REF-MISMATCH',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_PENDING_ADMIN_REVIEW, $payout->status);
        $this->assertNull($payout->fund_transaction_out_id);
        $this->assertSame(
            $outCountBefore,
            FundTransaction::where('wallet_id', $this->providerWallet->id)
                ->where('direction', FundTransaction::DIRECTION_OUT)
                ->count()
        );
        $this->assertDatabaseMissing('fund_transactions', [
            'wallet_id' => $this->providerWallet->id,
            'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
            'provider_payout_id' => $payout->id,
        ]);
    }

    #[Test]
    public function cannot_confirm_when_provider_wallet_balance_is_insufficient(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);
        $this->providerWallet->update(['balance' => 10.00]);

        $outCountBefore = FundTransaction::where('wallet_id', $this->providerWallet->id)
            ->where('direction', FundTransaction::DIRECTION_OUT)
            ->count();

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout), [
                'reference_number' => 'REF-LOW-BALANCE',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_PENDING_ADMIN_REVIEW, $payout->status);
        $this->assertNull($payout->fund_transaction_out_id);
        $this->assertSame('10.00', (string) $this->providerWallet->fresh()->balance);
        $this->assertSame(
            $outCountBefore,
            FundTransaction::where('wallet_id', $this->providerWallet->id)
                ->where('direction', FundTransaction::DIRECTION_OUT)
                ->count()
        );
        $this->assertDatabaseMissing('fund_transactions', [
            'wallet_id' => $this->providerWallet->id,
            'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
            'provider_payout_id' => $payout->id,
        ]);
    }

    #[Test]
    public function rejected_payout_makes_earnings_eligible_again(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.reject', $payout), [
                'admin_note' => 'no',
            ])
            ->assertRedirect();

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_REJECTED, $payout->status);
        $this->assertSame($this->admin->id, $payout->rejected_by);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'provider_payout.payout_request_rejected',
            'causer_id' => $this->admin->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-13 00:00:00', config('app.timezone')));
        $ids2 = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $this->assertCount(1, $ids2);
        $this->assertSame('60.00', (string) ProviderPayout::findOrFail($ids2[0])->amount);
    }

    #[Test]
    public function cancelled_payout_makes_earnings_eligible_again_without_wallet_out_transaction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.cancel', $payout), [
                'admin_note' => 'cancel and regenerate',
            ])
            ->assertRedirect(route('admin.finances.provider-payouts.show', $payout));

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_CANCELLED, $payout->status);
        $this->assertSame($this->admin->id, $payout->cancelled_by);
        $this->assertNull($payout->fund_transaction_out_id);
        $this->assertDatabaseMissing('fund_transactions', [
            'wallet_id' => $this->providerWallet->id,
            'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
            'provider_payout_id' => $payout->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'provider_payout.payout_request_cancelled',
            'causer_id' => $this->admin->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-13 00:00:00', config('app.timezone')));
        $ids2 = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $this->assertCount(1, $ids2);
        $this->assertSame('60.00', (string) ProviderPayout::findOrFail($ids2[0])->amount);
    }

    #[Test]
    public function admin_can_upload_receipt_before_confirming_payout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);

        // storeReceipt() returns redirect()->back(); pin previous URL so the assertion matches real UX.
        $this->actingAs($this->admin)
            ->from(route('admin.finances.provider-payouts.show', $payout))
            ->post(route('admin.finances.provider-payouts.receipt.store', $payout), [
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.finances.provider-payouts.show', $payout))
            ->assertSessionHas('success');

        $receiptPath = $payout->fresh()->receipt_path;
        $this->assertNotNull($receiptPath);
        Storage::disk('local')->assertExists($receiptPath);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'provider_payout.payout_receipt_uploaded',
            'causer_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.finances.provider-payouts.confirm', $payout->fresh()), [
                'reference_number' => 'REF-PREUPLOADED',
            ])
            ->assertRedirect(route('admin.finances.provider-payouts.show', $payout));

        $payout->refresh();
        $this->assertSame(ProviderPayout::STATUS_TRANSFERRED, $payout->status);
        $this->assertSame($receiptPath, $payout->receipt_path);
    }

    #[Test]
    public function unrelated_in_transactions_are_excluded_from_payout(): void
    {
        $donor = User::factory()->create();
        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
        ]);
        FundTransaction::create([
            'wallet_id' => $this->providerWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 10.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => null,
        ]);
        $this->providerWallet->refresh();

        Carbon::setTestNow(Carbon::parse('2026-04-06 00:00:00', config('app.timezone')));
        $ids = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $payout = ProviderPayout::findOrFail($ids[0]);
        $this->assertSame('60.00', (string) $payout->amount);
        $this->assertSame(1, $payout->items()->count());
    }
}
