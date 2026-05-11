<?php

namespace Tests\Unit\Notifications;

use App\Contracts\NotificationServiceInterface;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\DonationSuccessAdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DonationSuccessAdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function uses_database_channel_only(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50.00,
            'is_guest' => true,
            'is_anonymous' => true,
        ]);

        $notification = new DonationSuccessAdminNotification($payment);
        $admin = User::factory()->create();

        $this->assertSame(['database'], $notification->via($admin));
    }

    #[Test]
    public function payload_contains_correct_fields_for_registered_donor(): void
    {
        $donor = User::factory()->create(['name' => 'Generous Donor']);

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 250.75,
            'is_guest' => false,
            'is_anonymous' => false,
        ]);

        $notification = new DonationSuccessAdminNotification($payment);
        $admin = User::factory()->create();
        $payload = $notification->toArray($admin);

        $this->assertSame('donation_success_admin', $payload['type']);
        $this->assertSame($payment->id, $payload['payment_id']);
        $this->assertSame(250.75, $payload['amount']);
        $this->assertFalse($payload['is_guest']);
        $this->assertSame('Generous Donor', $payload['donor_name']);
        $this->assertStringContainsString('250.75', $payload['message']);
        $this->assertStringContainsString('Generous Donor', $payload['message']);
        $this->assertSame(route('admin.finances.payments.index'), $payload['url']);
    }

    #[Test]
    public function payload_contains_guest_label_for_guest_donation(): void
    {
        $payment = Payment::create([
            'sponsor_id' => null,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100.00,
            'is_guest' => true,
            'is_anonymous' => true,
        ]);

        $notification = new DonationSuccessAdminNotification($payment);
        $admin = User::factory()->create();
        $payload = $notification->toArray($admin);

        $this->assertSame('donation_success_admin', $payload['type']);
        $this->assertTrue($payload['is_guest']);
        $this->assertNull($payload['donor_name']);
        $this->assertStringContainsString('100.00', $payload['message']);
        $this->assertStringContainsString('guest', strtolower($payload['message']));
    }

    #[Test]
    public function service_sends_notification_to_all_admins(): void
    {
        Notification::fake();

        $admin1 = $this->admin();
        $admin2 = $this->admin();
        $donor = $this->donor();

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 75.00,
            'is_guest' => false,
            'is_anonymous' => false,
        ]);

        app(NotificationServiceInterface::class)->sendDonationSuccessToAdmins($payment);

        Notification::assertSentTo($admin1, DonationSuccessAdminNotification::class);
        Notification::assertSentTo($admin2, DonationSuccessAdminNotification::class);
        Notification::assertNotSentTo($donor, DonationSuccessAdminNotification::class);
    }

    #[Test]
    public function service_does_not_send_to_non_admin_users(): void
    {
        Notification::fake();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $donor = $this->donor();
        $recipient = $this->recipient();

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 60.00,
            'is_guest' => false,
            'is_anonymous' => false,
        ]);

        app(NotificationServiceInterface::class)->sendDonationSuccessToAdmins($payment);

        Notification::assertNotSentTo($donor, DonationSuccessAdminNotification::class);
        Notification::assertNotSentTo($recipient, DonationSuccessAdminNotification::class);
    }

    #[Test]
    public function notification_config_registers_donation_success_admin_type(): void
    {
        $config = config('notifications.types.donation_success_admin');

        $this->assertNotNull($config);
        $this->assertSame('success', $config['icon']);
        $this->assertSame('check-circle', $config['icon_svg']);
    }
}
