<?php

namespace Tests\Feature\Provider;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Support\PseudonymousRequestId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;

    protected $recipient;

    protected $menuItem;

    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        // Users
        $this->provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->provider->assignRole('provider');

        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->recipient->assignRole('recipient');

        // System wallet (city fund) - balance from fund_transactions; allocation requires Payment records
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
            'amount' => 100,
        ]);
        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 100,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        // Provider profile (creates provider ewallet via booted)
        if (! $this->provider->providerProfile) {
            ProviderProfile::create([
                'user_id' => $this->provider->id,
                'full_name_ar' => 'مزود اختبار',
                'full_name_en' => 'Test Provider',
                'phone_number' => '966501234567',
                'email' => $this->provider->email,
                'business_name_ar' => 'مطعم اختبار',
                'business_name_en' => 'Test Restaurant',
                'unified_number' => '7000123456',
                'business_category' => ['restaurant'],
                'address_ar' => 'الرياض',
                'address_en' => 'Riyadh',
                'city' => 'Riyadh',
                'region' => 'central',
            ]);
        }

        // Menu Item
        $this->menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Burger',
            'price' => 50.00,
            'is_active' => true,
        ]);

        // Create a Pending Request
        $this->request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->request->items()->create([
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 50.00,
        ]);
    }

    #[Test]
    public function provider_can_view_incoming_requests()
    {
        $response = $this->actingAs($this->provider)
            ->get(route('provider.requests.index'));

        $response->assertStatus(200);
        $response->assertSee(PseudonymousRequestId::make($this->request->id), false);
        $response->assertDontSee($this->recipient->name);
        $response->assertSee('50.00');
    }

    #[Test]
    public function provider_can_adopt_request()
    {
        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'adopt',
            ]);

        $response->assertRedirect();

        // APPROVED = provider adopted (funding_source PROVIDER_ADOPTION, CITY_FUND not affected)
        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'APPROVED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ]);
    }

    #[Test]
    public function provider_can_approve_request()
    {
        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'approve',
            ]);

        $response->assertRedirect();

        // approve = provider accepts with City Fund, status REDEEMABLE (transfer happens at redemption, not here)
        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);
    }

    #[Test]
    public function provider_cannot_approve_when_city_fund_has_insufficient_balance()
    {
        // Drain system wallet to 10 SAR (insufficient for 50 SAR request)
        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->first();
        FundTransaction::where('wallet_id', $systemWallet->id)->delete();
        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 10,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);
        $systemWallet->syncBalance();

        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'approve',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'REQUESTED',
        ]);
    }

    #[Test]
    public function provider_can_reject_request_with_reason()
    {
        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'reject',
                'rejection_reason_code' => 'Item Unavailable',
                'rejection_reason_note' => 'Out of stock',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'REJECTED',
            'rejection_reason_code' => 'Item Unavailable',
            'rejection_reason_note' => 'Out of stock',
        ]);
    }

    #[Test]
    public function provider_cannot_act_on_non_pending_request()
    {
        $this->request->update(['status' => 'FULFILLED']);

        $response = $this->actingAs($this->provider)
            ->put(route('provider.requests.update', $this->request->id), [
                'action' => 'reject',
                'rejection_reason_code' => 'Other',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error'); // Controller should return back with error

        $this->assertDatabaseHas('requests', [
            'id' => $this->request->id,
            'status' => 'FULFILLED', // Should not change
        ]);
    }

    #[Test]
    public function provider_cannot_view_others_requests()
    {
        $otherProvider = User::factory()->create();
        $otherProvider->assignRole('provider');

        $response = $this->actingAs($otherProvider)
            ->get(route('provider.requests.show', $this->request->id));

        $response->assertStatus(404); // Scoped query should not find it
    }
}
