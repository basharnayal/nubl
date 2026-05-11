<?php

namespace Tests\Unit\Models;

use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\PendingAllocation;
use App\Models\ProviderMenuItem;
use App\Models\ProviderPayout;
use App\Models\ProviderPayoutItem;
use App\Models\Request as RequestModel;
use App\Models\RequestItem;
use App\Models\RequestPaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinancialModelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function pending_allocation_model_casts_amount_and_resolves_relations(): void
    {
        $provider = User::factory()->create();
        $recipient = User::factory()->create();

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 15.50,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        $pending = PendingAllocation::create([
            'request_id' => $request->id,
            'provider_id' => $provider->id,
            'amount' => 15.50,
            'paused_by' => 'global',
        ])->fresh();

        $this->assertSame('15.50', (string) $pending->amount);
        $this->assertTrue($pending->request->is($request));
        $this->assertTrue($pending->provider->is($provider));
    }

    #[Test]
    public function provider_payout_item_model_casts_amount_and_resolves_relations(): void
    {
        $provider = User::factory()->create();
        $wallet = \App\Models\Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $fundTransaction = FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 30.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => null,
        ]);

        $payout = ProviderPayout::create([
            'provider_id' => $provider->id,
            'provider_wallet_id' => $wallet->id,
            'week_start_at' => now()->subWeek(),
            'week_end_at' => now()->subDay(),
            'scheduled_at' => now(),
            'amount' => 30.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
        ]);

        $item = ProviderPayoutItem::create([
            'provider_payout_id' => $payout->id,
            'fund_transaction_id' => $fundTransaction->id,
            'amount' => 30.00,
        ])->fresh();

        $this->assertSame('30.00', (string) $item->amount);
        $this->assertTrue($item->providerPayout->is($payout));
        $this->assertTrue($item->fundTransaction->is($fundTransaction));
    }

    #[Test]
    public function request_item_model_casts_prices_and_resolves_relations(): void
    {
        $provider = User::factory()->create();
        $recipient = User::factory()->create();

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Meal',
            'price' => 15.00,
            'category' => 'food',
            'is_active' => true,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 30.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        $item = RequestItem::create([
            'request_id' => $request->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'price_snapshot' => 15.00,
        ])->fresh();

        $this->assertSame('15.00', (string) $item->price_snapshot);
        $this->assertSame('30.00', (string) $item->line_total);
        $this->assertTrue($item->request->is($request));
        $this->assertTrue($item->menuItem->is($menuItem));
    }

    #[Test]
    public function payment_persists_is_guest_and_is_anonymous_flags_via_mass_assignment(): void
    {
        // Regression: is_guest and is_anonymous must be in $fillable so they are not
        // silently dropped by mass-assignment, which would break guest redirect logic
        // and expose "anonymous" donors on the top-donors page.
        $guest = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_INITIATED,
            'amount' => 50.00,
            'is_guest' => true,
            'is_anonymous' => true,
        ])->fresh();

        $this->assertTrue($guest->is_guest, 'is_guest must persist — guest redirect depends on it');
        $this->assertTrue($guest->is_anonymous, 'is_anonymous must persist — privacy feature depends on it');

        $donor = User::factory()->create();

        $anonymous = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_INITIATED,
            'amount' => 100.00,
            'is_guest' => false,
            'is_anonymous' => true,
        ])->fresh();

        $this->assertFalse($anonymous->is_guest);
        $this->assertTrue($anonymous->is_anonymous, 'Anonymous sponsor flag must persist to honour privacy preference');
    }

    #[Test]
    public function request_payment_link_model_casts_amount_and_resolves_payment_and_request_relations(): void
    {
        $provider = User::factory()->create();
        $recipient = User::factory()->create();
        $donor = User::factory()->create();

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 40.00,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 40.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);

        $link = RequestPaymentLink::create([
            'payment_id' => $payment->id,
            'request_id' => $request->id,
            'amount' => 40.00,
        ])->fresh();

        $this->assertSame('40.00', (string) $link->amount);
        $this->assertTrue($link->payment->is($payment));
        $this->assertTrue($link->request->is($request));
    }
}
