<?php

namespace App\Notifications;

use App\Models\OrderRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * FR-11.2: Notify admins within 60 seconds of a successful redemption/transaction.
 */
class RedemptionCompletedAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        private OrderRedemption $redemption,
        private int $providerId
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'redemption_completed',
            'title' => __('QR Code Redeemed Successfully'),
            'body' => __('Request #:request_id was redeemed by provider #:provider_id.', [
                'request_id' => $this->redemption->request_id,
                'provider_id' => $this->providerId,
            ]),
            'redemption_id' => $this->redemption->id,
            'request_id' => $this->redemption->request_id,
            'provider_id' => $this->providerId,
            'action_url' => route('admin.requests.index'),
            'icon' => 'info',
            'color' => 'success',
            'happened_at' => now()->toIso8601String(),
        ];
    }
}
