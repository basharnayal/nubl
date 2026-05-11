<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DonationSuccessAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment
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
        $amount = number_format((float) $this->payment->amount, 2);
        $donor = $this->payment->sponsor;

        return [
            'type' => 'donation_success_admin',
            'payment_id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'is_guest' => $this->payment->is_guest,
            'donor_name' => $donor?->name,
            'message' => $this->payment->is_guest
                ? __('A guest donation of :amount SAR was completed successfully.', ['amount' => $amount])
                : __('Donation of :amount SAR from :name was completed successfully.', ['amount' => $amount, 'name' => $donor?->name ?? __('Unknown')]),
            'url' => route('admin.finances.payments.index'),
        ];
    }
}
