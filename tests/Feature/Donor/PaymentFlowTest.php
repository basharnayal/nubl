<?php

namespace Tests\Feature\Donor;

use App\Http\Services\MyFatoorahService;
use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $donor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $this->donor = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->donor->assignRole('donor');

        Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
    }

    #[Test]
    public function payment_success_flow_creates_payment_and_fund_transaction(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => '12345',
            'status' => Payment::STATUS_PENDING,
            'amount' => 100,
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Paid',
                'invoice_id' => '12345',
                'raw_response' => [],
            ]);

        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => '12345']))
            ->assertRedirect(route('donor.payments.success', ['payment_id' => $payment->id]));

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);

        $this->assertTrue(
            FundTransaction::where('payment_id', $payment->id)->exists(),
            'FundTransaction IN should exist for succeeded payment'
        );

        $this->assertTrue(
            DatabaseNotification::where('notifiable_id', $this->donor->id)
                ->where('notifiable_type', User::class)
                ->whereJsonContains('data->type', 'donation_receipt')
                ->exists(),
            'Donation receipt notification should be created for donor'
        );
    }

    #[Test]
    public function payment_failed_flow_does_not_create_fund_transaction(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => '67890',
            'status' => Payment::STATUS_PENDING,
            'amount' => 50,
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Failed',
                'invoice_id' => '67890',
                'raw_response' => [],
            ]);

        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => '67890']))
            ->assertRedirect(route('donor.payments.failed', ['payment_id' => $payment->id]));

        $payment->refresh();
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);

        $this->assertFalse(
            FundTransaction::where('payment_id', $payment->id)->exists(),
            'No FundTransaction should exist for failed payment'
        );
    }

    #[Test]
    public function callback_idempotency_does_not_duplicate_fund_transactions(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => '11111',
            'status' => Payment::STATUS_PENDING,
            'amount' => 75,
        ]);

        $mockMyFatoorah = $this->createMock(MyFatoorahService::class);
        $mockMyFatoorah->method('getPaymentStatus')
            ->willReturn([
                'status' => 'Paid',
                'invoice_id' => '11111',
                'raw_response' => [],
            ]);

        $this->app->instance(MyFatoorahService::class, $mockMyFatoorah);

        $this->get(route('payments.callback', ['paymentId' => '11111']));
        $this->get(route('payments.callback', ['paymentId' => '11111']));

        $count = FundTransaction::where('payment_id', $payment->id)->count();
        $this->assertSame(1, $count, 'Only one FundTransaction should exist despite double callback');
    }
}
