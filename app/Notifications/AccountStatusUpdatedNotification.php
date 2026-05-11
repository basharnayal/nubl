<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public bool $isApproved,
        public ?string $rejectionReason = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isApproved) {
            return (new MailMessage)
                ->subject(__('Your account has been approved'))
                ->line(__('Your account has been approved.'))
                ->action(__('Go to dashboard'), route('dashboard'));
        }

        $mail = (new MailMessage)
            ->subject(__('Your application was not approved'))
            ->line(__('Your application was not approved.'));

        if (! empty($this->rejectionReason)) {
            $mail->line(__('Reason').': '.$this->rejectionReason);
        }

        return $mail
            ->line(__('You may submit a new application.'))
            ->action(__('Contact support'), url('/'));
    }

    public function toArray(object $notifiable): array
    {
        if ($this->isApproved) {
            return [
                'type' => 'account_approved',
                'message' => __('Your account has been approved.'),
                'subtitle' => __('Account update'),
                'url' => route('dashboard'),
            ];
        }

        return [
            'type' => 'account_rejected',
            'message' => __('Your application was not approved.'),
            'subtitle' => __('Account update'),
            'reason' => $this->rejectionReason,
            'url' => route('approval.pending'),
        ];
    }
}
