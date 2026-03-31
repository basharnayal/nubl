<?php

namespace App\Observers;

use App\Contracts\NotificationServiceInterface;
use App\Models\User;

class UserObserver
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    public function updated(User $user): void
    {
        if (! $user->wasChanged('status')) {
            return;
        }
        if ($user->status !== User::STATUS_PENDING_APPROVAL) {
            return;
        }
        if ($user->getOriginal('status') !== User::STATUS_REJECTED) {
            return;
        }
        if (! in_array($user->membership_type, [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER], true)) {
            return;
        }

        $this->notificationService->sendDocumentsResubmittedForReviewToAdmins($user);
    }
}
