<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Provider-facing status updates they did not initiate (cancel, admin decision).
 * Not queued so the bell updates immediately.
 */
class ProviderRequestStatusChangedNotification extends Notification
{
    public function __construct(
        public RequestModel $request,
        public string $status
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
        [$subject, $line] = $this->statusText();

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello!'))
            ->line($line)
            ->line(__('Request ID').': #'.$this->request->id)
            ->action(__('View Request'), route('provider.requests.show', $this->request->id));
    }

    public function toArray(object $notifiable): array
    {
        [, $line] = $this->statusText();

        return [
            'type' => 'provider_request_status_changed',
            'request_id' => $this->request->id,
            'status' => $this->status,
            'message' => $line,
            'subtitle' => __('Request status update'),
            'url' => route('provider.requests.show', $this->request->id),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function statusText(): array
    {
        return match ($this->status) {
            'CANCELLED' => [
                __('Request cancelled by recipient'),
                __('Request #:id was cancelled by the recipient.', ['id' => $this->request->id]),
            ],
            'ADMIN_APPROVED' => [
                __('Request approved by admin'),
                __('Request #:id was approved by admin and is ready for your action.', ['id' => $this->request->id]),
            ],
            'ADMIN_REJECTED' => [
                __('Request rejected by admin'),
                __('Request #:id was rejected by admin.', ['id' => $this->request->id]),
            ],
            default => [
                __('Request status updated'),
                __('Request #:id status is now :status.', [
                    'id' => $this->request->id,
                    'status' => $this->status,
                ]),
            ],
        };
    }
}
