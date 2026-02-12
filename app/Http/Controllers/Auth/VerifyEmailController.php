<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectAfterVerification($request);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->redirectAfterVerification($request);
    }

    /**
     * Redirect user after email verification. Pending approval users go to approval page.
     */
    protected function redirectAfterVerification(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->status === \App\Models\User::STATUS_PENDING_APPROVAL) {
            return redirect()->route('approval.pending');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
