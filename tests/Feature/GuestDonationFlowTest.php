<?php

namespace Tests\Feature;

use App\Contracts\NotificationServiceInterface;
use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\MyFatoorahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestDonationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
    }

    // ── Validation ─────────────────────────────────────────────────────

    #[Test]
    public function guest_initiate_validates_required_amount(): void
    {
        $response = $this->post(route('guest.donation.initiate'), []);

        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function guest_initiate_validates_minimum_amount(): void
    {
        $response = $this->post(route('guest.donation.initiate'), [
            'amount' => 0.5,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function guest_initiate_validates_maximum_amount(): void
    {
        $response = $this->post(route('guest.donation.initiate'), [
            'amount' => 1000000,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    // ── Initiation ─────────────────────────────────────────────────────

    #[Test]
    public function guest_initiate_creates_payment_and_redirects_to_gateway(): void
    {
        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('createInvoice')
            ->willReturn([
                'invoice_id' => 'guest-inv-001',
                'payment_url' => 'https://payment-gateway.example.com/pay/guest-inv-001',
                'raw_response' => [],
            ]);
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $response = $this->post(route('guest.donation.initiate'), [
            'amount' => 50,
        ]);

        $response->assertRedirect('https://payment-gateway.example.com/pay/guest-inv-001');

        $this->assertDatabaseHas('payments', [
            'sponsor_id' => null,
            'amount' => 50,
            'status' => Payment::STATUS_PENDING,
            'external_payment_id' => 'guest-inv-001',
            'is_guest' => true,
        ]);
    }

    #[Test]
    public function guest_initiate_handles_gateway_api_unavailable(): void
    {
        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('createInvoice')
            ->willThrowException(new \RuntimeException('Gateway API unavailable'));
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $response = $this->post(route('guest.donation.initiate'), [
            'amount' => 50,
        ]);

        $response->assertSessionHas('payment_reason', 'api_unavailable');

        $payment = Payment::where('is_guest', true)->latest()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);

        $response->assertRedirect(route('guest.donation.failed', ['token' => $payment->idempotency_key]));
    }

    // ── Success callback ───────────────────────────────────────────────

    #[Test]
    public function guest_payment_success_flow_creates_fund_transaction(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-pay-100',
            'status' => Payment::STATUS_PENDING,
            'amount' => 100,
            'is_guest' => true,
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Paid',
                'invoice_id' => 'guest-pay-100',
                'raw_response' => [],
            ]);
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => 'guest-pay-100']))
            ->assertRedirect(route('guest.donation.success', ['token' => $payment->fresh()->idempotency_key]));

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);

        $this->assertTrue(
            FundTransaction::where('payment_id', $payment->id)->exists(),
            'FundTransaction IN should exist for succeeded guest payment'
        );

        $this->assertDatabaseHas('fund_transactions', [
            'payment_id' => $payment->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'direction' => FundTransaction::DIRECTION_IN,
        ]);

        $systemWallet = Ewallet::where('owner_type', 'SYSTEM')->firstOrFail();
        $this->assertSame('100.00', (string) $systemWallet->fresh()->balance);
    }

    #[Test]
    public function guest_payment_success_does_not_send_notification(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-pay-nonotif',
            'status' => Payment::STATUS_PENDING,
            'amount' => 25,
            'is_guest' => true,
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Paid',
                'invoice_id' => 'guest-pay-nonotif',
                'raw_response' => [],
            ]);
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $mockNotifications = $this->createMock(NotificationServiceInterface::class);
        $mockNotifications->expects($this->never())
            ->method('sendDonationReceipt');
        $this->app->instance(NotificationServiceInterface::class, $mockNotifications);

        $this->get(route('payments.callback', ['paymentId' => 'guest-pay-nonotif']))
            ->assertRedirect(route('guest.donation.success', ['token' => $payment->fresh()->idempotency_key]));

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
    }

    // ── Failed callback ────────────────────────────────────────────────

    #[Test]
    public function guest_payment_failed_flow_redirects_to_guest_failed(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-fail-200',
            'status' => Payment::STATUS_PENDING,
            'amount' => 50,
            'is_guest' => true,
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Failed',
                'invoice_id' => 'guest-fail-200',
                'raw_response' => [],
            ]);
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => 'guest-fail-200']))
            ->assertRedirect(route('guest.donation.failed', ['token' => $payment->fresh()->idempotency_key]));

        $payment->refresh();
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);

        $this->assertFalse(
            FundTransaction::where('payment_id', $payment->id)->exists(),
            'No FundTransaction should exist for failed guest payment'
        );
    }

    // ── Idempotency ────────────────────────────────────────────────────

    #[Test]
    public function guest_callback_idempotency_does_not_duplicate_fund_transactions(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-idem-300',
            'status' => Payment::STATUS_PENDING,
            'amount' => 75,
            'is_guest' => true,
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Paid',
                'invoice_id' => 'guest-idem-300',
                'raw_response' => [],
            ]);
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => 'guest-idem-300']));
        $this->get(route('payments.callback', ['paymentId' => 'guest-idem-300']));

        $count = FundTransaction::where('payment_id', $payment->id)->count();
        $this->assertSame(1, $count, 'Only one FundTransaction should exist despite double callback');
    }

    // ── Success/Failed pages ───────────────────────────────────────────

    #[Test]
    public function guest_success_page_loads_for_guest_payment(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-success-page',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
            'is_guest' => true,
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $response = $this->get(route('guest.donation.success', ['token' => $payment->idempotency_key]));
        $response->assertOk();
        $response->assertViewIs('guest-donation.success');
        $response->assertViewHas('payment');
    }

    #[Test]
    public function guest_success_page_does_not_load_non_guest_payment(): void
    {
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
        $donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $donor->assignRole('donor');

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'donor-only-page',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
            'is_guest' => false,
        ]);

        $response = $this->get(route('guest.donation.success', ['token' => $payment->idempotency_key ?? 'fake-token']));
        $response->assertOk();
        $response->assertViewHas('payment', null);
    }

    #[Test]
    public function guest_receipt_loads_via_uuid_token(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-receipt-token',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
            'is_guest' => true,
            'idempotency_key' => $key = \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $response = $this->get(route('guest.donation.receipt', ['token' => $key]));
        $response->assertOk();
        $response->assertViewIs('guest-donation.receipt');
        $response->assertViewHas('payment');
    }

    #[Test]
    public function guest_receipt_rejects_numeric_id(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'guest-receipt-numid',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50,
            'is_guest' => true,
        ]);

        $response = $this->get("/donate/receipt/{$payment->id}");
        $response->assertNotFound();
    }

    #[Test]
    public function guest_receipt_rejects_non_guest_payment_token(): void
    {
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
        $donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $donor->assignRole('donor');

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'donor-receipt-noaccess',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 200,
            'is_guest' => false,
            'idempotency_key' => $key = \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $response = $this->get(route('guest.donation.receipt', ['token' => $key]));
        $response->assertNotFound();
    }

    #[Test]
    public function guest_failed_page_loads(): void
    {
        $response = $this->get(route('guest.donation.failed'));
        $response->assertOk();
        $response->assertViewIs('guest-donation.failed');
    }

    // ── Existing donor flow is unchanged ───────────────────────────────

    #[Test]
    public function authenticated_donor_payment_still_redirects_to_donor_success(): void
    {
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
        $donor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $donor->assignRole('donor');

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'donor-flow-500',
            'status' => Payment::STATUS_PENDING,
            'amount' => 200,
            'is_guest' => false,
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Paid',
                'invoice_id' => 'donor-flow-500',
                'raw_response' => [],
            ]);
        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => 'donor-flow-500']))
            ->assertRedirect(route('donor.payments.success', ['payment_id' => $payment->id]));

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
    }
}
