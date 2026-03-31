<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->action(__('View Request'), route('recipient.requests.show', $this->request->id));
    }

    public function toArray(object $notifiable): array
    {
        [, $line] = $this->statusText();

        return [
            'type' => 'request_status_changed',
            'request_id' => $this->request->id,
            'status' => $this->status,
            'message' => $line,
            'subtitle' => __('Request status update'),
            'url' => route('recipient.requests.show', $this->request->id),
        ];
    }

    private function statusText(): array
    {
        return match ($this->status) {
            'APPROVED' => [
                __('Request approved'),
                __('Your request #:id has been approved by the provider (adopted).', ['id' => $this->request->id]),
            ],
            'REDEEMABLE' => [
                __('Request is redeemable'),
                __('Your request #:id is now redeemable. You can use the QR code to redeem it.', ['id' => $this->request->id]),
            ],
            'FULFILLED' => [
                __('Request fulfilled'),
                __('Your request #:id has been fulfilled successfully.', ['id' => $this->request->id]),
            ],
            'REJECTED' => [
                __('Request rejected'),
                __('Your request #:id has been rejected.', ['id' => $this->request->id]),
            ],
            'ADMIN_APPROVED' => [
                __('Request approved by admin'),
                __('Your request #:id was approved by admin and is being processed.', ['id' => $this->request->id]),
            ],
            'ADMIN_REJECTED' => [
                __('Request rejected by admin'),
                __('Your request #:id was rejected by admin.', ['id' => $this->request->id]),
            ],
            default => [
                __('Request status updated'),
                __('Your request #:id status changed to :status.', [
                    'id' => $this->request->id,
                    'status' => $this->status,
                ]),
            ],
        };
    }
}
