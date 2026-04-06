<?php

namespace App\Notifications;

use App\Models\ProviderPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProviderPayoutPendingAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProviderPayout $providerPayout
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'provider_payout_pending_review',
            'message' => __('notifications.provider_payout_pending_review_message', [
                'amount' => (string) $this->providerPayout->amount,
            ]),
            'subtitle' => 'notifications.provider_payout_pending_review_subtitle',
            'url' => route('admin.finances.provider-payouts.show', $this->providerPayout),
            'provider_payout_id' => $this->providerPayout->id,
        ];
    }
}
