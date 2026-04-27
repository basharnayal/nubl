<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Single place for all post-authentication redirect decisions.
 * Order: phone verification → email verification → approval status → dashboard.
 */
class PostAuthRedirector
{
    public function redirect(User $user): RedirectResponse
    {
        if (config('app.phone_verification_enabled', true) && ! $user->hasVerifiedPhone()) {
            return redirect()->route('verification.phone');
        }

        if (config('app.email_verification_enabled', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED], true)) {
            return redirect()->route('approval.pending');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
