<?php

namespace App\Http\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AccountApprovalPendingNotification;
use App\Notifications\DocumentsResubmittedForReviewNotification;
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
        $admins = User::role('admin')->get();
        if ($user->status === User::STATUS_PENDING_APPROVAL
            && in_array($user->membership_type, [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER], true)) {
            $admins->each(fn ($admin) => $admin->notify(new AccountApprovalPendingNotification($user)));

            return;
        }

        $admins->each(fn ($admin) => $admin->notify(new NewUserRegisteredNotification($user)));
    }

    public function sendDocumentsResubmittedForReviewToAdmins(User $user): void
    {
        User::role('admin')->get()->each(
            fn ($admin) => $admin->notify(new DocumentsResubmittedForReviewNotification($user))
        );
    }
}
