<?php

namespace Tests\Feature\Notifications;

use App\Contracts\NotificationServiceInterface;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\DonationReceiptNotification;
use App\Notifications\ProviderNewRequestNotification;
use App\Notifications\ProviderRequestStatusChangedNotification;
use App\Notifications\RequestStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationCatalogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function donation_receipt_targets_donor_with_payment_payload(): void
    {
        Notification::fake();

        $donor = User::factory()->create();
        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 123.45,
        ]);

        app(NotificationServiceInterface::class)->sendDonationReceipt($payment);

        Notification::assertSentTo(
            $donor,
            DonationReceiptNotification::class,
            function (DonationReceiptNotification $notification) use ($donor, $payment) {
                $payload = $notification->toArray($donor);

                $this->assertSame('donation_receipt', $payload['type']);
                $this->assertSame($payment->id, $payload['payment_id']);
                $this->assertSame(123.45, $payload['amount']);
                $this->assertStringContainsString('123.45', $payload['message']);
                $this->assertSame(route('donor.donations.index'), $payload['url']);

                return true;
            }
        );
    }

    #[Test]
    public function new_request_notification_targets_provider_with_request_payload(): void
    {
        Notification::fake();

        [$recipient, $provider, $request] = $this->createRequest();

        app(NotificationServiceInterface::class)->sendNewRequestToProvider($request);

        Notification::assertSentTo(
            $provider,
            ProviderNewRequestNotification::class,
            function (ProviderNewRequestNotification $notification) use ($provider, $request) {
                $payload = $notification->toArray($provider);

                $this->assertSame('provider_new_request', $payload['type']);
                $this->assertSame($request->id, $payload['request_id']);
                $this->assertStringContainsString((string) $request->id, $payload['message']);
                $this->assertStringContainsString('42.50', $payload['message']);
                $this->assertSame(route('provider.requests.show', $request->id), $payload['url']);

                return true;
            }
        );
        Notification::assertNotSentTo($recipient, ProviderNewRequestNotification::class);
    }

    #[Test]
    public function request_status_changed_targets_recipient_with_status_payload(): void
    {
        Notification::fake();

        [$recipient, $provider, $request] = $this->createRequest();

        app(NotificationServiceInterface::class)->sendRequestStatusChanged($request, 'REDEEMABLE');

        Notification::assertSentTo(
            $recipient,
            RequestStatusChangedNotification::class,
            function (RequestStatusChangedNotification $notification) use ($recipient, $request) {
                $payload = $notification->toArray($recipient);

                $this->assertSame('request_status_changed', $payload['type']);
                $this->assertSame($request->id, $payload['request_id']);
                $this->assertSame('REDEEMABLE', $payload['status']);
                $this->assertStringContainsString((string) $request->id, $payload['message']);
                $this->assertSame(route('recipient.requests.show', $request->id), $payload['url']);

                return true;
            }
        );
        Notification::assertNotSentTo($provider, RequestStatusChangedNotification::class);
    }

    #[Test]
    public function provider_status_changed_only_sends_supported_status_payloads(): void
    {
        Notification::fake();

        [, $provider, $request] = $this->createRequest();

        app(NotificationServiceInterface::class)->sendRequestStatusChangedToProvider($request, 'REDEEMABLE');
        Notification::assertNothingSent();

        app(NotificationServiceInterface::class)->sendRequestStatusChangedToProvider($request, 'CANCELLED');

        Notification::assertSentTo(
            $provider,
            ProviderRequestStatusChangedNotification::class,
            function (ProviderRequestStatusChangedNotification $notification) use ($provider, $request) {
                $payload = $notification->toArray($provider);

                $this->assertSame('provider_request_status_changed', $payload['type']);
                $this->assertSame($request->id, $payload['request_id']);
                $this->assertSame('CANCELLED', $payload['status']);
                $this->assertStringContainsString((string) $request->id, $payload['message']);
                $this->assertSame(route('provider.requests.show', $request->id), $payload['url']);

                return true;
            }
        );
    }

    /**
     * @return array{User, User, RequestModel}
     */
    private function createRequest(): array
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 42.50,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        return [$recipient, $provider, $request];
    }
}
