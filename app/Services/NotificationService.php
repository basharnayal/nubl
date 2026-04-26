<?php

namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\AccountApprovalPendingNotification;
use App\Notifications\AccountStatusUpdatedNotification;
use App\Notifications\DocumentsResubmittedForReviewNotification;
use App\Notifications\DonationReceiptNotification;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\ProviderNewRequestNotification;
use App\Notifications\ProviderRequestStatusChangedNotification;
use App\Notifications\RequestStatusChangedNotification;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    private function safeNotify(User $user, object $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            // Notifications should never break core flows (e.g., cancel request).
            Log::warning('Notification failed to send', [
                'notification' => $notification::class,
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function sendDonationReceipt(Payment $payment): void
    {
        $donor = $payment->sponsor;

        if ($donor) {
            $this->safeNotify($donor, new DonationReceiptNotification($payment));
        }
    }

    /**
     * Pending recipient/provider: every admin gets {@see AccountApprovalPendingNotification}.
     * Other new users (e.g. donor): {@see NewUserRegisteredNotification} to all admins.
     */
    public function sendNewUserRegisteredToAdmins(User $user): void
    {
        $admins = User::role('admin')->get();
        if (
            $user->status === User::STATUS_PENDING_APPROVAL
            && in_array($user->membership_type, [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER], true)
        ) {
            $admins->each(fn($admin) => $this->safeNotify($admin, new AccountApprovalPendingNotification($user)));

            return;
        }

        $admins->each(fn($admin) => $this->safeNotify($admin, new NewUserRegisteredNotification($user)));
    }

    /** Notify every admin when a rejected applicant resubmits (see {@see UserObserver}). */
    public function sendDocumentsResubmittedForReviewToAdmins(User $user): void
    {
        User::role('admin')->get()->each(
            fn($admin) => $this->safeNotify($admin, new DocumentsResubmittedForReviewNotification($user))
        );
    }

    public function sendAccountStatusUpdated(User $user, bool $isApproved, ?string $rejectionReason = null): void
    {
        $this->safeNotify($user, new AccountStatusUpdatedNotification($user, $isApproved, $rejectionReason));
    }

    public function sendRequestStatusChanged(RequestModel $request, string $status): void
    {
        $recipient = $request->recipient;
        if (!$recipient) {
            return;
        }

        $this->safeNotify($recipient, new RequestStatusChangedNotification($request, $status));
    }

    public function sendNewRequestToProvider(RequestModel $request): void
    {
        $provider = $request->provider;
        if (!$provider) {
            return;
        }

        $this->safeNotify($provider, new ProviderNewRequestNotification($request));
    }

    public function sendRequestStatusChangedToProvider(RequestModel $request, string $status): void
    {
        if (!in_array($status, ['CANCELLED', 'ADMIN_APPROVED', 'ADMIN_REJECTED'], true)) {
            return;
        }

        $provider = $request->provider;
        if (!$provider) {
            return;
        }

        $this->safeNotify($provider, new ProviderRequestStatusChangedNotification($request, $status));
    }

    public function sendNewRequestToAdmins(RequestModel $request): void
    {
        $admins = User::role('admin')->get();
        $admins->each(fn($admin) => $this->safeNotify($admin, new \App\Notifications\NewPendingAdminRequestNotification($request)));
    }
}
