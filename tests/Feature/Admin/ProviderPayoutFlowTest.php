<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderPayout;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Services\ProviderPayoutGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
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

        Carbon::setTestNow(Carbon::parse('2026-04-13 00:00:00', config('app.timezone')));
        $ids2 = app(ProviderPayoutGenerationService::class)->generateWeeklyAt(Carbon::now());
        Carbon::setTestNow();

        $this->assertCount(1, $ids2);
        $this->assertSame('60.00', (string) ProviderPayout::findOrFail($ids2[0])->amount);
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
