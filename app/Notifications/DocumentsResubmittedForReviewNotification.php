<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Admin: a previously rejected applicant resubmitted documents and is pending review again.
 */
class DocumentsResubmittedForReviewNotification extends Notification
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
            'type' => 'documents_resubmitted_for_review',
            'user_id' => $this->applicant->id,
            'membership_type' => $this->applicant->membership_type,
            'message' => __('notifications.documents_resubmitted_message', [
                'name' => $this->applicant->name,
                'type' => $typeLabel,
            ]),
            'subtitle' => 'notifications.documents_resubmitted_subtitle',
            'url' => route('admin.users.application', $this->applicant),
            'happened_at' => now()->toIso8601String(),
        ];
    }
}
