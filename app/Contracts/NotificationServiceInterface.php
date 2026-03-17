<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Models\User;

interface NotificationServiceInterface
{
    public function sendDonationReceipt(Payment $payment): void;

    public function sendNewUserRegisteredToAdmins(User $user): void;
}
