<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationControllerCoverageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_mark_single_notification_and_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $notification = new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return [
                    'type' => 'test_notification',
                    'message' => 'Test notification',
                    'url' => '/test',
                ];
            }
        };

        $user->notify($notification);
        $user->notify($notification);

        $targetId = $user->fresh()->notifications()->oldest('id')->value('id');
        $this->assertNotNull($targetId);

        $this->actingAs($user)
            ->postJson(route('notifications.read', $targetId))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($user->fresh()->notifications()->find($targetId)->read_at);

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
