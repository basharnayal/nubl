<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Admin: a recipient or provider application is awaiting review (first submission).
 */
class AccountApprovalPendingNotification extends Notification
{
    public function __construct(
        public User $applicant
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = match ($this->applicant->membership_type) {
            User::MEMBERSHIP_RECIPIENT => __('Recipient'),
            User::MEMBERSHIP_PROVIDER => __('Provider'),
            default => $this->applicant->membership_type,
        };

        return [
            'type' => 'account_approval_pending',
            'user_id' => $this->applicant->id,
            'membership_type' => $this->applicant->membership_type,
            'message' => __('notifications.account_approval_pending_message', [
                'name' => $this->applicant->name,
                'type' => $typeLabel,
            ]),
            'subtitle' => 'notifications.account_approval_pending_subtitle',
            'url' => route('admin.users.application', $this->applicant),
            'happened_at' => now()->toIso8601String(),
        ];
    }
}
