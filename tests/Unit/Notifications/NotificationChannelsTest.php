<?php

namespace Tests\Unit\Notifications;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\AccountStatusUpdatedNotification;
use App\Notifications\NewPendingAdminRequestNotification;
use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function account_status_updated_uses_database_and_mail_channels_when_email_exists(): void
    {
        $user = User::factory()->create();

        $notification = new AccountStatusUpdatedNotification($user, true);

        $channels = $notification->via((object) ['email' => 'user@example.com']);

        $this->assertSame(['database', 'mail'], $channels);
    }

    #[Test]
    public function account_status_updated_uses_database_only_when_email_is_missing(): void
    {
        $user = User::factory()->create();

        $notification = new AccountStatusUpdatedNotification($user, false, 'Missing document');

        $channels = $notification->via((object) ['email' => null]);

        $this->assertSame(['database'], $channels);
    }

    #[Test]
    public function account_status_updated_mail_for_approved_user_points_to_dashboard(): void
    {
        $user = User::factory()->create();

        $notification = new AccountStatusUpdatedNotification($user, true);
        $mail = $notification->toMail($user);

        $this->assertSame(__('Your account has been approved'), $mail->subject);
        $this->assertSame(__('Go to Dashboard'), $mail->actionText);
        $this->assertSame(route('dashboard'), $mail->actionUrl);
    }

    #[Test]
    public function account_status_updated_array_for_rejection_contains_reason_and_pending_url(): void
    {
        $user = User::factory()->create();

        $notification = new AccountStatusUpdatedNotification($user, false, 'Invalid proof');
        $payload = $notification->toArray($user);

        $this->assertSame('account_rejected', $payload['type']);
        $this->assertSame('Invalid proof', $payload['reason']);
        $this->assertSame(route('approval.pending'), $payload['url']);
    }

    #[Test]
    public function new_pending_admin_request_payload_contains_admin_route_and_request_data(): void
    {
        $recipient = User::factory()->create(['name' => 'Recipient One']);
        $provider = User::factory()->create();

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 25.00,
            'status' => 'REQUESTED',
        ]);

        $notification = new NewPendingAdminRequestNotification($request->fresh('recipient'));
        $payload = $notification->toArray($provider);

        $this->assertSame('new_pending_admin_request', $payload['type']);
        $this->assertStringContainsString((string) $request->id, $payload['body']);
        $this->assertStringContainsString('Recipient One', $payload['body']);
        $this->assertSame(route('admin.requests.index'), $payload['action_url']);
    }

    #[Test]
    public function new_user_registered_payload_maps_membership_type_to_human_label(): void
    {
        $registeredUser = User::factory()->create([
            'name' => 'Provider User',
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);

        $notification = new NewUserRegisteredNotification($registeredUser);
        $payload = $notification->toArray($registeredUser);

        $this->assertSame('new_user_registered', $payload['type']);
        $this->assertSame($registeredUser->id, $payload['user_id']);
        $this->assertStringContainsString('Provider User', $payload['message']);
        $this->assertStringContainsString((string) __('Provider'), $payload['message']);
        $this->assertSame(route('admin.users.pending'), $payload['url']);
    }
}

