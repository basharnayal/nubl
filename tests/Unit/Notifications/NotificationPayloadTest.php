<?php

namespace Tests\Unit\Notifications;

use App\Models\ProviderMenuItem;
use App\Models\User;
use App\Notifications\AdminToggleMenuItemNotification;
use App\Notifications\WeeklyAllowanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationPayloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function weekly_allowance_notification_supports_mail_and_database_payload_for_scheduled_event(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);
        app()->setLocale('en');

        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $actor = User::factory()->create(['name' => 'Admin Actor']);

        $notification = new WeeklyAllowanceNotification(
            'scheduled',
            350.00,
            '2026-05-03T00:00:00+03:00',
            $actor
        );

        $channels = $notification->via((object) ['email' => $recipient->email]);
        $this->assertSame(['database', 'mail'], $channels);

        $mail = $notification->toMail($recipient);
        $this->assertSame(__('Weekly allowance change scheduled'), $mail->subject);
        $this->assertStringContainsString(__('Action by').': Admin Actor', implode(' ', $mail->introLines));

        $payload = $notification->toArray($recipient);
        $this->assertSame('weekly_allowance_changed', $payload['type']);
        $this->assertSame('scheduled', $payload['event']);
        $this->assertSame(350.00, $payload['limit_sar']);
        $this->assertSame('2026-05-03T00:00:00+03:00', $payload['effective_at']);
        $this->assertSame(route('recipient.dashboard'), $payload['url']);
    }

    #[Test]
    public function weekly_allowance_notification_handles_applied_and_default_events_without_email(): void
    {
        $recipient = User::factory()->create(['email' => 'recipient-no-mail-check@example.test']);

        $applied = new WeeklyAllowanceNotification('applied', 400.00);
        $default = new WeeklyAllowanceNotification('something_else', 410.00);

        $this->assertSame(['database'], $applied->via((object) ['email' => null]));
        $this->assertSame(['database'], $default->via((object) ['email' => null]));

        $appliedPayload = $applied->toArray($recipient);
        $defaultPayload = $default->toArray($recipient);

        $this->assertStringContainsString('400.00', $appliedPayload['message']);
        $this->assertSame(__('Weekly allowance'), $defaultPayload['subtitle']);
    }

    #[Test]
    public function admin_toggle_menu_item_notification_builds_expected_channels_mail_and_database_payload(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'email' => 'provider@example.test',
        ]);

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Family Meal',
            'price' => 30.00,
            'is_active' => true,
        ]);

        $blockedNotification = new AdminToggleMenuItemNotification($menuItem, true);
        $unblockedNotification = new AdminToggleMenuItemNotification($menuItem, false);

        $this->assertSame(['database', 'mail'], $blockedNotification->via((object) ['email' => $provider->email]));
        $this->assertSame(['database'], $blockedNotification->via((object) ['email' => null]));

        $blockedMail = $blockedNotification->toMail($provider);
        $unblockedMail = $unblockedNotification->toMail($provider);
        $this->assertSame(__('Menu Item Status Update'), $blockedMail->subject);
        $this->assertSame(route('provider.menu-items.index'), $blockedMail->actionUrl);
        $this->assertStringContainsString(__('blocked'), implode(' ', $blockedMail->introLines));
        $this->assertStringContainsString(__('unblocked'), implode(' ', $unblockedMail->introLines));

        $blockedPayload = $blockedNotification->toArray($provider);
        $this->assertSame('menu_item_status_changed', $blockedPayload['type']);
        $this->assertSame($menuItem->id, $blockedPayload['menu_item_id']);
        $this->assertTrue($blockedPayload['is_blocked']);
        $this->assertSame(route('provider.menu-items.index'), $blockedPayload['url']);
    }
}
