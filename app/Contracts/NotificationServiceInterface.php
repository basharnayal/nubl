<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;

interface NotificationServiceInterface
{
    public function sendDonationReceipt(Payment $payment): void;

    public function sendNewUserRegisteredToAdmins(User $user): void;

    public function sendDocumentsResubmittedForReviewToAdmins(User $user): void;

    public function sendAccountStatusUpdated(User $user, bool $isApproved, ?string $rejectionReason = null): void;

    public function sendRequestStatusChanged(RequestModel $request, string $status): void;

    public function sendNewRequestToProvider(RequestModel $request): void;

    public function sendRequestStatusChangedToProvider(RequestModel $request, string $status): void;

    public function sendNewRequestToAdmins(RequestModel $request): void;
}
