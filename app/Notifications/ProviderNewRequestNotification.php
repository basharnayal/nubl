<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the provider when a recipient submits a new order request.
 * Not queued so the bell updates immediately.
 */
class ProviderNewRequestNotification extends Notification
{
    public function __construct(
        public RequestModel $request
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
        $line = __('You have a new request (#:id) for :amount SAR.', [
            'id' => $this->request->id,
            'amount' => number_format((float) $this->request->reserved_amount, 2),
        ]);

        return (new MailMessage)
            ->subject(__('New request received'))
            ->greeting(__('Hello!'))
            ->line($line)
            ->action(__('View Request'), route('provider.requests.show', $this->request->id));
    }

    public function toArray(object $notifiable): array
    {
        $message = __('You have a new request (#:id) for :amount SAR.', [
            'id' => $this->request->id,
            'amount' => number_format((float) $this->request->reserved_amount, 2),
        ]);

        return [
            'type' => 'provider_new_request',
            'request_id' => $this->request->id,
            'message' => $message,
            'subtitle' => __('New request'),
            'url' => route('provider.requests.show', $this->request->id),
        ];
    }
}
