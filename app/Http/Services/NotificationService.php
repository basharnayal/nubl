<?php

namespace App\Http\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\AccountStatusUpdatedNotification;
use App\Notifications\DonationReceiptNotification;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\RequestStatusChangedNotification;

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

    public function sendAccountStatusUpdated(User $user, bool $isApproved, ?string $rejectionReason = null): void
    {
        $user->notify(new AccountStatusUpdatedNotification($user, $isApproved, $rejectionReason));
    }

    public function sendRequestStatusChanged(RequestModel $request, string $status): void
    {
        $recipient = $request->recipient;
        if (!$recipient) {
            return;
        }

        $recipient->notify(new RequestStatusChangedNotification($request, $status));
    }
}
