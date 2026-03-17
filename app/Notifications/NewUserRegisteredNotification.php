<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Notifies admins when a new user has registered.
 * Database channel only (no mail) so admins see it in the panel.
 */
class NewUserRegisteredNotification extends Notification
{
    public function __construct(
        public User $registeredUser
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $name = $this->registeredUser->name;
        $type = $this->registeredUser->membership_type;
        $typeLabel = match ($type) {
            'donor' => __('Donor'),
            'recipient' => __('Recipient'),
            'provider' => __('Provider'),
            default => $type,
        };

        return [
            'type' => 'new_user_registered',
            'user_id' => $this->registeredUser->id,
            'message' => __(':name registered as :type.', [
                'name' => $name,
                'type' => $typeLabel,
            ]),
            'url' => route('admin.users.pending'),
        ];
    }
}
