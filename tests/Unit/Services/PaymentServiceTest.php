<?php

namespace Tests\Unit\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MyFatoorahService;
use App\Services\PaymentService;
use App\Services\SystemWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $donor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donor = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_DONOR,
        ]);
    }

    #[Test]
    public function initiate_returns_existing_payment_for_non_failed_idempotency_key(): void
    {
        $existing = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 25.00,
            'idempotency_key' => '77777777-7777-7777-7777-777777777777',
        ]);

        $service = $this->makeService(
            myFatoorah: $this->createMock(MyFatoorahService::class),
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $resolved = $service->initiateSponsorPayment(
            $this->donor->id,
            99.99,
            false,
            '77777777-7777-7777-7777-777777777777'
        );

        $this->assertTrue($resolved->is($existing));
        $this->assertSame(1, Payment::query()->count());
    }

    #[Test]
    public function initiate_creates_payment_when_no_existing_idempotency_record_exists(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')->once();

        $service = $this->makeService(
            myFatoorah: $this->createMock(MyFatoorahService::class),
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: $audit
        );

        $payment = $service->initiateSponsorPayment(
            $this->donor->id,
            55.25,
            false,
            '88888888-8888-8888-8888-888888888888'
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'sponsor_id' => $this->donor->id,
            'status' => Payment::STATUS_INITIATED,
            'idempotency_key' => '88888888-8888-8888-8888-888888888888',
        ]);
    }

    #[Test]
    public function callback_redirects_to_failed_when_gateway_status_lookup_throws(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 30.00,
            'external_payment_id' => 'cb-throw-1',
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willThrowException(new \RuntimeException('gateway timeout'));

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleCallback(Request::create('/payments/callback', 'GET', ['paymentId' => 'cb-throw-1']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
    }

    #[Test]
    public function callback_redirects_to_failed_when_gateway_response_shape_is_unexpected(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 30.00,
            'external_payment_id' => 'cb-invalid-shape',
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willReturn([
            'status' => 'Paid',
            'invoice_id' => '',
            'raw_response' => [],
        ]);

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleCallback(Request::create('/payments/callback', 'GET', ['paymentId' => 'cb-invalid-shape']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
    }

    #[Test]
    public function callback_redirects_to_failed_when_paid_status_targets_unknown_payment(): void
    {
        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willReturn([
            'status' => 'Paid',
            'invoice_id' => 'unknown-invoice',
            'raw_response' => [],
        ]);

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleCallback(Request::create('/payments/callback', 'GET', ['paymentId' => 'unknown-invoice']));

        $this->assertStringContainsString(route('guest.donation.failed'), $response->getTargetUrl());
    }

    #[Test]
    public function callback_marks_payment_succeeded_when_fund_transaction_already_exists(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 70.00,
            'external_payment_id' => 'cb-existing-fund',
        ]);

        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => $this->donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 70.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => null,
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willReturn([
            'status' => 'Paid',
            'invoice_id' => 'cb-existing-fund',
            'raw_response' => [],
        ]);

        $systemWallet = $this->createMock(SystemWalletService::class);
        $systemWallet->expects($this->never())->method('addFundsFromDonation');

        $notifications = $this->createMock(NotificationServiceInterface::class);
        $notifications->expects($this->never())->method('sendDonationReceipt');

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $systemWallet,
            notificationService: $notifications,
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleCallback(Request::create('/payments/callback', 'GET', ['paymentId' => 'cb-existing-fund']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
    }

    #[Test]
    public function error_url_redirects_to_failed_when_payment_identifier_is_missing(): void
    {
        $service = $this->makeService(
            myFatoorah: $this->createMock(MyFatoorahService::class),
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleError(Request::create('/payments/error', 'GET'));

        $this->assertStringContainsString(route('donor.payments.failed'), $response->getTargetUrl());
    }

    #[Test]
    public function error_url_marks_payment_failed_when_gateway_response_shape_is_invalid(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 40.00,
            'external_payment_id' => 'err-invalid-shape',
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willReturn([
            'status' => 'Failed',
            'invoice_id' => '',
            'raw_response' => [],
        ]);

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleError(Request::create('/payments/error', 'GET', ['paymentId' => 'err-invalid-shape']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
    }

    #[Test]
    public function error_url_marks_payment_failed_when_gateway_verification_throws(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 40.00,
            'external_payment_id' => 'err-throw',
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willThrowException(new \RuntimeException('provider unavailable'));

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleError(Request::create('/payments/error', 'GET', ['paymentId' => 'err-throw']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertTrue((bool) ($payment->fresh()->notes['gateway_unavailable'] ?? false));
    }

    #[Test]
    public function callback_handles_empty_invoice_identifier_from_gateway_as_ambiguous(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 45.00,
            'external_payment_id' => 'cb-empty-invoice',
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willReturn([
            'status' => 'Paid',
            'invoice_id' => '',
            'raw_response' => [],
        ]);

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleCallback(Request::create('/payments/callback', 'GET', ['paymentId' => 'cb-empty-invoice']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
    }

    #[Test]
    public function callback_casts_non_string_status_and_non_scalar_invoice_id_from_gateway(): void
    {
        $payment = Payment::create([
            'sponsor_id' => $this->donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_PENDING,
            'amount' => 45.00,
            'external_payment_id' => 'cb-status-cast',
        ]);

        $myFatoorah = $this->createMock(MyFatoorahService::class);
        $myFatoorah->method('getPaymentStatus')->willReturn([
            'status' => 123,
            'invoice_id' => ['bad'],
            'raw_response' => [],
        ]);

        $service = $this->makeService(
            myFatoorah: $myFatoorah,
            systemWallet: $this->createMock(SystemWalletService::class),
            notificationService: $this->createMock(NotificationServiceInterface::class),
            auditService: Mockery::mock(AuditService::class)->shouldIgnoreMissing()
        );

        $response = $service->handleCallback(Request::create('/payments/callback', 'GET', ['paymentId' => 'cb-status-cast']));

        $this->assertStringContainsString('payment_id='.$payment->id, $response->getTargetUrl());
        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
    }

    private function makeService(
        MyFatoorahService $myFatoorah,
        SystemWalletService $systemWallet,
        NotificationServiceInterface $notificationService,
        AuditService $auditService
    ): PaymentService {
        return new PaymentService($myFatoorah, $systemWallet, $auditService, $notificationService);
    }
}
