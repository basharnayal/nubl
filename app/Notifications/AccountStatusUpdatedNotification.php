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
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isApproved) {
            return (new MailMessage)
                ->subject(__('Your account has been approved'))
                ->greeting(__('Hello!'))
                ->line(__('Great news! Your account is now approved and active.'))
                ->action(__('Go to Dashboard'), route('dashboard'))
                ->line(__('Thank you for using NUBL.'));
        }

        $mail = (new MailMessage)
            ->subject(__('Your account application was rejected'))
            ->greeting(__('Hello!'))
            ->line(__('Your account application was reviewed and rejected at this time.'));

        if (!empty($this->rejectionReason)) {
            $mail->line(__('Reason') . ': ' . $this->rejectionReason);
        }

        return $mail
            ->action(__('Contact Support'), url('/'))
            ->line(__('You can review your application details and apply again later.'));
    }

    public function toArray(object $notifiable): array
    {
        if ($this->isApproved) {
            return [
                'type' => 'account_approved',
                'message' => __('Your account has been approved and is now active.'),
                'subtitle' => __('Account approval update'),
                'url' => route('dashboard'),
            ];
        }

        return [
            'type' => 'account_rejected',
            'message' => __('Your account application was rejected.'),
            'subtitle' => __('Account approval update'),
            'reason' => $this->rejectionReason,
            'url' => route('approval.pending'),
        ];
    }
}
