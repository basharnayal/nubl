<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Donation receipt - runs synchronously so notification appears immediately
 * without requiring a queue worker (QUEUE_CONNECTION=database would delay it).
 */
class DonationReceiptNotification extends Notification
{

    public function __construct(
        public Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->payment->amount, 2);
        $date = $this->payment->created_at->translatedFormat('F j, Y \a\t H:i');

        return (new MailMessage)
            ->subject(__('Donation Receipt - :amount SAR', ['amount' => $amount]))
            ->greeting(__('Thank you for your donation!'))
            ->line(__('Your donation was successful. Here is your receipt:'))
            ->line(__('Amount') . ': ' . $amount . ' ' . __('SAR'))
            ->line(__('Date') . ': ' . $date)
            ->line(__('Payment ID') . ': #' . $this->payment->id)
            ->line(__('Your contribution has been added to the city fund and will help those in need.'))
            ->action(__('View My Donations'), route('donor.donations.index'))
            ->line(__('Thank you for supporting those in need.'));
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'donation_receipt',
            'payment_id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'message' => __('Thank you! Your donation of :amount SAR was successful.', [
                'amount' => number_format((float) $this->payment->amount, 2),
            ]),
            'url' => route('donor.donations.index'),
        ];
    }
}
