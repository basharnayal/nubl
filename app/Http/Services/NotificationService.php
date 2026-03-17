<?php

namespace App\Http\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\DonationReceiptNotification;
use App\Notifications\NewUserRegisteredNotification;

class NotificationService implements NotificationServiceInterface
{
    public function sendDonationReceipt(Payment $payment): void
    {
        $donor = $payment->sponsor;

        if ($donor) {
            $donor->notify(new DonationReceiptNotification($payment));
        }
    }

    public function sendNewUserRegisteredToAdmins(User $user): void
    {
        User::role('admin')->get()->each(
            fn ($admin) => $admin->notify(new NewUserRegisteredNotification($user))
        );
    }
}
